<?php

namespace Overcode\XePlugin\DynamicFactory;

use Xpressengine\Config\ConfigEntity;
use Xpressengine\Plugins\Comment\Handler as CommentHandler;

class CommentManager{

    /**
     * @var CommentHandler
     */
    protected $commentHandler;


    /**
     * create instance
     *
     * @param CommentHandler         $commentHandler    comment handler
     */
    public function __construct(
        CommentHandler $commentHandler
    ) {
        $this->commentHandler = $commentHandler;
    }


    public function hasCommentConfig($instance_id){
        return $this->commentHandler->getInstanceId($instance_id);
    }

    /**
     * create comment config(create new comment instance)
     *
     * @param ConfigEntity $config document config entity
     * @return mixed
     */
    public function createCommentConfig($instance_id)
    {
        $this->commentHandler->createInstance($instance_id);
        $this->commentHandler->configure(
            $this->commentHandler->getInstanceId($instance_id),
            ['useWysiwyg' => true]
        );

        return $this->commentHandler->getInstanceId($instance_id);
    }

    public function dropCommentConfig($instance_id){
        $this->commentHandler->drop($this->commentHandler->getInstanceId($instance_id));
    }

}
