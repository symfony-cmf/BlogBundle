<?php

namespace Symfony\Cmf\Bundle\BlogBundle\Repository;
use Doctrine\ODM\PHPCR\DocumentRepository;
use Symfony\Cmf\Bundle\BlogBundle\Document\Post;

class PostRepository extends DocumentRepository
{ 
    public function searchQuery($options)
    {
        $options = array_merge(array(
            'blog_uuid' => null,
        ), $options);
        $qb = $this->createQueryBuilder();

        $criterias = array();

        if ($options['blog_id']) {
            $criterias[] = $qb->expr()->descendant($options['blog_id']);
        }

        if (count($criterias) == 2) {
            $qb->where(
                $qb->expr()->andX($criterias[0], $criterias[1])
            );
        } elseif (count($criterias) == 1) {
            $qb->where(current($criterias));
        }

        $qb->orderBy('date', 'DESC');
        $q = $qb->getQuery();

        return $q;
    }

    public function search($options)
    {
        $q = $this->searchQuery($options);
        $res = $q->execute();
        return $res;
    }

    public function fetchOneBySlug($slug)
    {
        $qb = $this->createQueryBuilder();
        $qb->where($qb->expr()->eq('slug', $slug));
        $qb->setMaxResults(1);
        $q = $qb->getQuery();
        $post = $q->getSingleResult();

        return $post;
    }

    public function fetchNextPost(Post $post)
    {
        $post = $this->fetchSiblingPost($post);
        return $post;
    }

    public function fetchPrevPost(Post $post)
    {
        $post = $this->fetchSiblingPost($post, true);
        return $post;
    }

    public function fetchSiblingPost(Post $post, $prev = false)
    {
        $qb = $this->createQueryBuilder();
        $qb->setMaxResults(1);

        // hmm, if two adjacent posts were created in the same second this
        // will not work.
        if (true === $prev) {
            $qb->where($qb->expr()->lt('date', $post->getDate()));
            $qb->orderBy('date', 'desc');
        } else {
            $targetDate = clone $post->getDate();
            $targetDate->modify('+1 minute');
            $qb->where($qb->expr()->gt('date', $targetDate));
            $qb->orderBy('date', 'asc');
        }

        $q = $qb->getQuery();
        $post = $q->getOneOrNullResult();

        return $post;
    }
}
