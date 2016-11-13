<?php
/**
 * Created by PhpStorm.
 * User: hursit_topal
 * Date: 12/11/16
 * Time: 01:43
 */

namespace Follower\TwitterBundle\Service;

use Doctrine\ORM\EntityManager;
use Follower\CoreBundle\Wrapper\FollowerWrapper;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class Liker
 * @package Follower\TwitterBundle\Service
 */
class Unfollower
{
    CONST UNFOLLOW_AFTER = '+3 day';

    /** @var  Container $container */
    protected $container;

    /** @var  EntityManager $em */
    protected $em;

    /** @var  FollowerWrapper $wrapper */
    protected $wrapper;


    /**
     * DailyFollower constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        $this->em = $container->get('doctrine.orm.entity_manager');

        $this->wrapper = $container->get('core_unfollower_wrapper');
    }

    public function unfollow()
    {
        $followers = $this->wrapper->getFollows(1);

        $current = new \DateTime();

        foreach ($followers as $follower) {
            if (!$follower['user_name']) continue;

            $unfollowTime = (new \DateTime($follower['createdAt']))->modify(self::UNFOLLOW_AFTER);

            if ($current < $unfollowTime) continue;

            $item = $this->getProfileFactory()->profile($follower['user_name']);

            if ($item->isFollowingBack()) continue;

            if(!$item->isFollowing()) continue;

            if($this->getUnfollowFactory()->unfollow($item->getUserId())) {
                $this->container->get('follower_event_dispatcher')->dispatchUnfollowed(array(
                    'id' => $follower['id'],
                    'un_followed' => true,
                    'provider_id' => 1,
                    'user_id' => $item->getUserId(),
                    'user_name' => $item->getUserName()
                ));

                var_dump(array(
                    'id' => $follower['id'],
                    'un_followed' => true,
                    'provider_id' => 1,
                    'user_id' => $item->getUserId(),
                    'user_name' => $item->getUserName()
                ));
            }

            sleep(60);

        }
    }

    /**
     * @return Factory\Unfollow|object
     */
    public function getUnfollowFactory()
    {
        return $this->container->get('twitter_unfollow_factory');
    }

    /**
     * @return Factory\Profile|object
     */
    public function getProfileFactory()
    {
        return $this->container->get('twitter_profile_factory');
    }
}