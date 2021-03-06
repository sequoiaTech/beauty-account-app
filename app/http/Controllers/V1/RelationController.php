<?php

namespace App\Http\Controllers\V1;


use App\Http\Models\Accounts;
use App\Http\Models\Relation;
use Exception;

class RelationController extends ControllerBase
{

    private $relationModel;


    private $accountModel;


    public function initialize()
    {
        parent::initialize();
        $this->relationModel = new Relation();
        $this->accountModel = new Accounts();
    }


    public function storeAction()
    {
        if (empty($this->data['uid'])) {
            return $this->response->setJsonContent([
                'code'    => 400,
                'message' => 'missing argv uid'
            ]);
        }
        if ($this->data['uid'] == $this->uid) {
            return $this->response->setJsonContent([
                'code'    => 400,
                'message' => 'invalid argv uid'
            ]);
        }
        if (!$this->accountModel->exists($this->data['uid'])) {
            return $this->response->setJsonContent([
                'code'    => 400,
                'message' => 'uid is not exists'
            ]);
        }

        $this->relationModel->addFollow($this->data['uid'], $this->uid);

        return $this->response->setJsonContent([
            'code'    => 200,
            'message' => 'success'
        ]);
    }


    public function destroyAction($uid)
    {
        if ($uid == $this->uid) {
            return $this->response->setJsonContent([
                'code'    => 400,
                'message' => 'invalid argv uid'
            ]);
        }

        $this->relationModel->delFollow($uid, $this->uid);

        return $this->response->setJsonContent([
            'code'    => 200,
            'message' => 'success'
        ]);
    }


    public function indexAction()
    {
        $type = $this->request->get('type');
        switch ($type) {
            case 'followers':
                $data = $this->relationModel->listFollowers($this->uid);
                break;
            case 'following':
                $data = $this->relationModel->listFollowing($this->uid);
                break;
            default:
                throw new Exception('invalid argv type');
        }
        return $this->response->setJsonContent([
            'code'    => 200,
            'message' => 'success',
            'payload' => [
                'num'  => count($data),
                'list' => $data
            ]
        ]);
    }

}
