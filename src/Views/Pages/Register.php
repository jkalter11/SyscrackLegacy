<?php
    namespace Framework\Views\Pages;

    /**
     * Lewis Lancaster 2017
     *
     * Class Register
     *
     * @package Framework\Views\Pages
     */

    use Flight;
    use Framework\Application\Container;
    use Framework\Application\Mailer;
    use Framework\Application\Settings;
    use Framework\Application\Utilities\PostHelper;
    use Framework\Exceptions\SyscrackException;
    use Framework\Syscrack\BetaKeys;
    use Framework\Syscrack\Register as Account;
    use Framework\Views\BaseClasses\Page as BaseClass;
    use Framework\Views\Structures\Page as Structure;

    class Register extends BaseClass implements Structure
    {

        /**
         * @var Mailer
         */

        protected $mailer;

        /**
         * Register constructor.
         */

        public function __construct()
        {

            parent::__construct( false, true, false, true );

            if (Container::getObject('session')->isLoggedIn())
            {

                Flight::redirect( Settings::getSetting('controller_index_root') . Settings::getSetting('controller_index_page') );
            }

            if( isset( $this->mailer ) == false )
            {

                $this->mailer = new Mailer();
            }
        }

        /**
         * Returns the pages mapping
         *
         * @return array
         */

        public function mapping()
        {

            return array(
                [
                    'GET /register/', 'page'
                ],
                [
                    'POST /register/', 'process'
                ]
            );
        }

        /**
         * Default page
         */

        public function page()
        {

            Flight::render('syscrack/page.register');
        }

        /**
         * Processes the register request
         */

        public function process()
        {

            if (PostHelper::hasPostData() == false)
            {

                $this->redirectError('Blank Form');
            }

            if (Settings::getSetting('user_allow_registrations') == false)
            {

                $this->redirectError('Registration is currently disabled, sorry...');
            }

            if (PostHelper::checkForRequirements(['username', 'password', 'email']) == false)
            {

                $this->redirectError('Missing Information');
            }

            $username = PostHelper::getPostData('username');

            $password = PostHelper::getPostData('password');

            $email = PostHelper::getPostData('email');

            if (empty($username) || empty($password) || empty($email))
            {

                $this->redirectError('Missing Information');
            }

            $register = new Account();

            if (strlen($password) < Settings::getSetting('registration_password_length'))
            {

                $this->redirectError('Your password is too small, it needs to be longer than ' . Settings::getSetting('registration_password_length') . ' characters');
            }

            if( Settings::getSetting('user_require_betakey') == true )
            {

                $betakeys = new BetaKeys();

                if( PostHelper::checkForRequirements(['betakey'] ) == false )
                {

                    $this->redirectError('A beta-key is required to signup');
                }

                $key = PostHelper::getPostData('betakey');

                if( $betakeys->hasBetaKey( $key ) == false )
                {

                    $this->redirectError('Sorry, that key is invalid or has already been used');
                }

                try
                {

                    $result = $register->register($username, $password, $email);
                }
                catch( SyscrackException $error )
                {

                    $this->redirectError( $error->getMessage() );
                }

                $betakeys->removeBetaKey( $key );

                if( Settings::getSetting('registration_verification') == true )
                {

                    $result = $this->sendEmail( $email, array('token' => $result ) );

                    if( $result == false )
                    {

                        $this->redirectError( $this->mailer->getErrorInfo() );
                    }

                    $this->redirect('verify');
                }
                else
                {

                    $this->redirect('verify?token=' . $result );
                }
            }
            else
            {

                try
                {

                    $result = $register->register($username, $password, $email);
                }
                catch( SyscrackException $error )
                {

                    $this->redirectError( $error->getMessage() );
                }

                if( Settings::getSetting('registration_verification') == true )
                {

                    $result = $this->sendEmail( $email, array('token' => $result, 'link' => Settings::getSetting('game_https_link') ) );

                    if( $result == false )
                    {

                        $this->redirectError( $this->mailer->getErrorInfo() );
                    }

                    $this->redirect('verify');
                }
                else
                {

                    $this->redirect('verify?token=' . $result );
                }
            }
        }

        private function sendEmail( $email, array $variables )
        {

            $body = $this->mailer->parse( $this->mailer->getTemplate('email.verify.php'), $variables );

            if( empty( $body ) )
            {

                throw new SyscrackException();
            }

            $result = $this->mailer->send( $body, 'Verify your email', $email );

            if( $result == false )
            {

                return false;
            }

            return true;
        }
    }