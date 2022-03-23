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
        $container = $this->setd();
        $session = $container->get('session');
        $session->start();
        $login = $session->get('login');
        $log = $_COOKIE['login'];
        $check = $this->request->get('log');

        if (($log || $login) && $check != 'logout') {
            $time = $container->get('datetime');
            $this->view->time = $time;
            $user = Users::find();
            $this->view->users = $user;
            $userid = $session->get('user_id');
            if ($userid) {
                $this->view->check = 1;
            }
        } else {
            $session->destroy();
            setcookie('login', 0, time() + (86400 * 30), "/");
            return $response->redirect('user/login');
        }
    }

    public function signupAction()
    {
        $response = new Response();
        if ($this->request->isPost() && $this->request->getPost('email') && $this->request->getPost()['name']) {
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
            $response->setStatusCode(400, 'Not Found');
            $response->setContent("Sorry, the page doesn't exist");
            $response->send();
            $this->view->message = 'please fill form!!';
        }

        $this->view->message = $response->getStatusCode();
    }


    public function loginAction()
    {
        $container = $this->setd();
        $session = $container->get('session');


        $response = new Response();
        $check = $this->request->isPost();
        if ($check) {
            $email = $this->request->getPost()['email'];
            $password = $this->request->getPost()['password'];
            $data = Users::findFirst(['conditions' => "email = '$email' AND password = '$password'"]);
            if ($data) {
                $remember = $this->request->getPost()['remember'];
                if ($remember == 'on') {
                    $cookie = new Cookies('login', 1);
                    $response->setCookies($cookie);
                    $response->send();
                    setcookie('login', 1, time() + (86400 * 30), "/");
                }
                $userid = $data->user_id;
                $session->start();
                $session->set('login', 1);
                $session->login = 1;
                return $response->redirect('/user');
            } else {
                unset($_POST);
                $_POST = array();
                $response->setStatusCode(403, 'Authentication Failed');
                $response->setContent("Authenication failed");
                $response->send();
                die;
                die;
            }
        }
    }

    public function getd()
    {
        return
            new Di();
    }
    public function setd()
    {
        $container = $this->getd();
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
