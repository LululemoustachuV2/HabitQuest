<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\QuestTemplate;
use App\Entity\User;
use App\Entity\UserQuest;
use App\Enum\UserQuestStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserQuest>
 */
class UserQuestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserQuest::class);
    }

    /**
     * @return UserQuest[]
     */
    public function findAllForUser(User $user): array
    {
        return $this->createQueryBuilder('uq')
            ->andWhere('uq.user = :user')
            ->setParameter('user', $user)
            ->orderBy('uq.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForUserAndTemplate(User $user, QuestTemplate $template, ?Event $event = null): ?UserQuest
    {
        $qb = $this->createQueryBuilder('uq')
            ->andWhere('uq.user = :user')
            ->andWhere('uq.questTemplate = :template')
            ->setParameter('user', $user)
            ->setParameter('template', $template);

        if ($event === null) {
            $qb->andWhere('uq.event IS NULL');
        } else {
            $qb->andWhere('uq.event = :event')->setParameter('event', $event);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return UserQuest[]
     */
    public function findInProgressByKindForUser(User $user, string $kind): array
    {
        return $this->createQueryBuilder('uq')
            ->join('uq.questTemplate', 't')
            ->andWhere('uq.user = :user')
            ->andWhere('uq.status = :status')
            ->andWhere('t.kind = :kind')
            ->setParameter('user', $user)
            ->setParameter('status', UserQuestStatus::IN_PROGRESS)
            ->setParameter('kind', $kind)
            ->getQuery()
            ->getResult();
    }
}
