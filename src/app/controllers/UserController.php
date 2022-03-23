<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;
use Phalcon\Session\Manager;
use Phalcon\Session\Adapter\Stream;
use Phalcon\Http\Response\Cookies;
use Phalcon\Di;
use Phalcon\Di\DiInterface;

class UserController extends Controller
{

    public function indexAction()
    {

        $response = new Response();

        /**
         * di container call
         */
        $container = $this->setd();
        $session = $container->get('session');
        $session->start();
        $login = $session->get('login');
        $log = $_COOKIE['login'];
        $check = $this->request->get('log');

        if (($log || $login) && $check != 'logout') {

            /**
             * fetching date time from datetime 
             */
            $time = $container->get('datetime');
            $this->view->time = $time;
            $user = Users::find();
            $this->view->users = $user;
        } else {
            $session->destroy();
            setcookie('login', 0, time() + (86400 * 30), "/");
            return $response->redirect('user/login');
        }
    }

    /**
     * signupAction()
     * controller function to handle signup view
     *
     * @return void
     */
    public function signupAction()
    {
        $response = new Response();
        if (
            $this->request->isPost() && $this->request->getPost('email')
            && $this->request->getPost()['name'] && $this->request->getPost()['password']
        ) {
            $user = new Users();
            $user->assign(
                $this->request->getPost(),
                [
                    'name',
                    'email',
                    'password',
                ]
            );

            $success = $user->save();
            if ($success) {
                unset($_POST);
                $_POST = array();
                return $response->redirect('/user');
            } else {
                $this->view->message = $user->getMessages();
            }
        } else {
            $this->view->message = 'please fill form!!';
        }
    }

    /**
     * loginAction
     * controller to handle login view
     *
     * @return void
     */
    public function loginAction()
    {

        /**
         * calling session di
         */
        $container = $this->setd();
        $session = $container->get('session');

        $response = new Response();

        /**
         * checking for post request
         */
        $check = $this->request->isPost();
        if ($check) {
            $email = $this->request->getPost()['email'];
            $password = $this->request->getPost()['password'];
            $data = Users::findFirst(['conditions' => "email = '$email' AND password = '$password'"]);
            if ($data) {

                /**
                 * if remember is checked setting cookie
                 */
                $remember = $this->request->getPost()['remember'];
                if ($remember == 'on') {
                    $cookie = new Cookies('login', 1);
                    $response->setCookies($cookie);
                    $response->send();
                    setcookie('login', 1, time() + (86400 * 30), "/");
                }
                $session->start();
                $session->set('login', 1);
                $session->login = 1;
                return $response->redirect('/user');
            } else {
                unset($_POST);
                $_POST = array();

                /**
                 * sending response 403 if authentication fails
                 */
                $response->setStatusCode(403, 'Authentication Failed');
                $response->setContent("Authenication failed");
                $response->send();
                die;
                die;
            }
        }
    }


    /**
     * getd()
     * function to initialize Di object
     *
     * @return void
     */
    public function getd()
    {
        return
            new Di();
    }

    /**
     * function to register various di services
     *
     * @return void
     */
    public function setd()
    {
        $container = $this->getd();


        /**
         * session service register
         */
        $container->set(
            'session',
            function () {
                $session = new Manager();
                $files = new Stream(
                    [
                        'savePath' => '/tmp',
                    ]
                );
                $session->setAdapter($files);
                return $session;
            }
        );

        /**
         * datetime service register
         */
        $container->set(
            'datetime',
            function () {
                $timestamp = time();
                $date_time = date("d-m-Y (D) H:i:s", $timestamp);
                return $date_time;
            }
        );
        return $container;
    }
}
