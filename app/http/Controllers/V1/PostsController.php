<?php

namespace App\Http\Controllers\V1;


use App\Http\Models\Posts;
use App\Providers\Components\UtilsTrait;

class PostsController extends ControllerBase
{

    use UtilsTrait;


    private $postsModel;


    public function initialize()
    {
        parent::initialize();
        $this->postsModel = new Posts();
    }


    // 获取 TODO :: 评论数量限制&预览
    public function showAction($postId)
    {
        if (!$posts = $this->postsModel->get($postId)) {
            return $this->response->setJsonContent([
                'code'    => 400,
                'message' => 'no posts data'
            ]);
        }
        unset($posts['_id']);

        if ($posts['uid'] != $this->uid) {
            // TODO :: 预览记录
        }

        // 合并用户数据
        $data = $this->utils->fillUserByCache($posts['uid'], ['name', 'gender', 'level', 'avatar']);
        $data['pid'] = $postId;
        foreach ($posts as $k => $info) {
            if (isset($data[$k])) {
                continue;
            }
            $data[$k] = $info;
        }
        unset($posts);

        // 匿名隐藏
        if (!empty($data['anonymous'])) {
            $data['uid'] = '';
            $data['name'] = 'anonymous';
            $data['avatar'] = '';
        }
        // 评论列表
        if (isset($data['comments'])) {
            $data['comments'] = $this->utils->fillUserByKey(
                $data['comments'], 'uid', ['name', 'gender', 'level', 'avatar']
            );
        }
        // 查看列表
        if (isset($data['viewers'])) {
            $data['viewers'] = $this->utils->fillUserbyCache(
                $data['viewers'], ['name', 'gender', 'level', 'avatar']
            );
        }

        return $this->response->setJsonContent([
            'code'    => 200,
            'message' => 'success',
            'payload' => $data
        ]);
    }


    // 创建
    public function storeAction()
    {
        $type = $this->filter($this->data['type'], 'alphanum', 'text');
        $content = $this->filter($this->data['content'], 'string', '');
        $files = $this->filter($this->data['files'], 'string', '');
        $locale = $this->filter($this->data['locale'], 'string', null);
        $anonymous = empty($this->data['anonymous']) ? 0 : 1;
        $lat = empty($this->data['lat']) ? null : (float)$this->data['lat'];
        $lng = empty($this->data['lng']) ? null : (float)$this->data['lng'];

        // 检查
        if ($type == 'text' && !$content) {
            return $this->response->setJsonContent(['code' => 400, 'message' => 'missing argv: content']);
        }
        if ($type != 'text' && !$files) {
            return $this->response->setJsonContent(['code' => 400, 'message' => 'missing argv: files']);
        }
        if (!in_array($type, ['text', 'picture', 'voice', 'video'])) {
            return $this->response->setJsonContent(['code' => 400, 'message' => 'error argv: type']);
        }

        // 属性
        $attach = [
            'lat' => $lat,
            'lng' => $lng,
        ];
        if ($locale) {
            $attach['locale'] = $locale;
        }
        if ($files && $type != 'text') {
            $attach += [$type => $files];
        }
        if ($anonymous) {
            $attach += ['anonymous' => 1];
        }

        // 发布
        if (!$postId = $this->postsModel->add($this->uid, $content, $attach)) {
            return $this->response->setJsonContent(['code' => 400, 'message' => 'failed']);
        }

        return $this->response->setJsonContent([
            'code'    => 200,
            'message' => 'success',
            'payload' => [
                'postId' => $postId
            ]
        ]);
    }


    // 删除
    public function destroyAction($id)
    {
        if (!$this->postsModel->del($this->uid, $id)) {
            return $this->response->setJsonContent([
                'code'    => 400,
                'message' => 'failed'
            ]);
        }

        return $this->response->setJsonContent([
            'code'    => 200,
            'message' => 'success'
        ]);
    }

}
