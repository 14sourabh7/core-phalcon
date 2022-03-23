<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;

use Phalcon\Http\Response\Cookies;


class UserController extends Controller
{

    public function indexAction()
    {

        $response = new Response();

        $session = $this->session;

        $login = $session->get('login');
        $log = $_COOKIE['login'];

        $check = $this->request->get('log');

        if (($log || $login) && $check != 'logout') {

            /**
             * fetching date time from datetime 
             */
            $time = $this->datetime;
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


        $session = $this->session;

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
            }
        }
    }
}
