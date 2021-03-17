<?php

namespace Overcode\XePlugin\DynamicFactory;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Session\SessionManager;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use Xpressengine\Document\DocumentHandler;

/**
 * Class IdentifyManager
 *
 * 비회원이 작성한 글의 인증 처리 관리
 *
 * @package Overcode\XePlugin\DynamicFactory
 */
class IdentifyManager
{
    /**
     * 인증 후 결과를 저장 할 세션 이름
     */
    const SESSION_NAME = 'IDENTIFY_KEY';

    /**
     * 인증 세션의 만료 시간을 저장할 세션 이름
     */
    const EXPIRE_SESSION_NAME = 'IDENTIFY_KEY_EXPIRE_TIME';

    /**
     * 인증 세션 유지 시간.
     */
    const EXPIRE_TIME = 600;

    /**
     * @var SessionManager
     */
    protected $session;

    /**
     * @var DocumentHandler
     */
    protected $document;

    /**
     * @var Hasher
     */
    protected $hasher;

    /**
     * create instance
     *
     * @param SessionManager  $session  session manager
     * @param DocumentHandler $document document handler
     * @param Hasher          $hasher   hasher
     */
    public function __construct(SessionManager $session, DocumentHandler $document, Hasher $hasher)
    {
        $this->session = $session;
        $this->document = $document;
        $this->hasher = $hasher;
    }

    /**
     * 암호화 된 비밀번호 반환
     *
     * @param string $value password
     * @return string
     */
    public function hash($value)
    {
        return $this->hasher->make($value);
    }

    /**
     * 비회원 작성 글 인증 확인
     *
     * @param CptDocument $doc
     * @param string $email email
     * @param string $certifyKey 인증 암호
     * @return bool
     */
    public function verify(CptDocument $doc, $email, $certifyKey)
    {
        if ($email != $doc->email) {
            return false;
        }

        return $this->hasher->check($certifyKey, $doc->certify_key);
    }

    /**
     * 한번 생성 한 세션은 EXPIRE_TIME 시간 만큼 유효함.
     *
     * @param string $id hashed certify key
     * @return string
     */
    public function getKey($id)
    {
        return self::SESSION_NAME . $id;
    }

    /**
     * 인증 세션 생성
     *
     * @param CptDocument $doc
     * @return void
     */
    public function create(CptDocument $doc)
    {
        $this->session->put($this->getKey($doc->id), [
            'certify_key' => $doc->certify_key,
            'expire' => $this->expireTime(),
        ]);
    }

    /**
     * get expire time
     *
     * @return int
     */
    private function expireTime()
    {
        return time() + self::EXPIRE_TIME;
    }

    /**
     * 인증 세션 반환
     *
     * @param CptDocument $doc
     * @return mixed
     */
    public function get(CptDocument $doc)
    {
        return $this->session->get($this->getKey($doc->id));
    }

    /**
     * 문서에 대한 인증이 유효한지 검사
     * 인증 암호 및 유효 시간 검사
     *
     * @param CptDocument $doc
     * @return bool
     */
    public function validate(CptDocument $doc)
    {
        $session = $this->get($doc);
        if ($doc->certify_key != $session['certify_key']) {
            return false;
        }

        // 세션 만료됨
        if ($session['expire'] < time()) {
            $this->destroy($doc);
            return false;
        }

        return true;
    }

    /**
     * 문서에 대해서 인증한 세션이 있는지 체크
     *
     * @param CptDocument $doc
     * @return bool
     */
    public function identified(CptDocument $doc)
    {
        $sessionName = $this->getKey($doc->id);
        if ($this->session->has($sessionName) === false) {
            return false;
        }

        if ($this->validate($doc) === false) {
            return false;
        }

        // 세션 갱신
        $this->destroy($doc);
        $this->create($doc);

        return true;
    }

    /**
     * destroy session
     *
     * @param CptDocument $doc
     * @return void
     */
    public function destroy(CptDocument $doc)
    {
        $this->session->remove($this->getKey($doc->id));
    }
}
