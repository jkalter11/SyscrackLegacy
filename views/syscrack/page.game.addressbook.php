<?php

    use Framework\Application\Container;
    use Framework\Syscrack\Game\AddressDatabase;
    use Framework\Syscrack\Game\Computers;
    use Framework\Syscrack\Game\Internet;
    use Framework\Syscrack\Game\Utilities\PageHelper;
    use Framework\Syscrack\Game\Viruses;

    $session = Container::getObject('session');

    if( $session->isLoggedIn() )
    {

        $session->updateLastAction();
    }

    if( isset( $pagehelper ) == false )
    {

        $pagehelper = new PageHelper();
    }

    if( isset( $computers) == false )
    {

        $computers= new Computers();
    }

    if( isset( $addressdatabase ) == false )
    {

        $addressdatabase = new AddressDatabase();
    }

    if( isset( $internet ) == false )
    {

        $internet = new Internet();
    }

    if( isset( $viruses ) == false )
    {

        $viruses = new Viruses();
    }
?>

<!DOCTYPE html>
<html>

    <?php

        Flight::render('syscrack/templates/template.header', array('pagetitle' => 'Syscrack | Game') );
    ?>
    <body>
        <div class="container">

            <?php

                Flight::render('syscrack/templates/template.navigation');
            ?>
            <div class="row">
                <div class="col-lg-12">
                    <h5 style="color: #ababab" class="text-uppercase">
                        Address Book
                    </h5>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4">
                    <div class="list-group">
                        <a href="/game/addressbook/" class="list-group-item active">
                            <h4 class="list-group-item-heading">Address Book</h4>
                            <p class="list-group-item-text">View all of your hacked addresses.</p>
                        </a>
                        <a href="/game/accountbook/" class="list-group-item">
                            <h4 class="list-group-item-heading">Account Book</h4>
                            <p class="list-group-item-text">View all of the accounts you have hacked.</p>
                        </a>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" aria-label="...">
                        <div class="input-group-btn">
                            <button class="btn btn-default">
                                Find
                            </button>
                        </div>
                    </div>
                    <div style="margin-top: 2.5%">

                        <?php

                            if( $addressdatabase->hasDatabase( $session->getSessionUser() ) == false )
                            {

                                ?>
                                    <div class="panel panel-danger">
                                        <div class="panel-heading">
                                            Addressbook Missing
                                        </div>
                                        <div class="panel-body">
                                            <p>
                                                Your addressbook appears to be missing, this is usually an error so please tell a developer
                                                you are having this problem.
                                            </p>
                                            <ul class="list-group">
                                                <li class="list-group-item">
                                                    Userid <span class="badge right"><?=$session->getSessionUser()?></span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                <?php
                            }
                            else
                            {

                                $addresses = $addressdatabase->getUserAddresses( $session->getSessionUser() );

                                if( empty( $addresses ))
                                {

                                    ?>
                                        <div class="panel panel-danger">
                                            <div class="panel-heading">
                                                Addressbook Empty
                                            </div>
                                            <div class="panel-body">
                                                Your addressbook appears to be empty, go hack somebody!
                                            </div>
                                        </div>
                                    <?php
                                }
                                else
                                {

                                    $addresses = array_reverse( $addresses );

                                    $removed = [];

                                    foreach( $addresses as $key=>$value )
                                    {

                                        if( $internet->ipExists( $value['ipaddress'] ) == false )
                                        {

                                            $removed[] = array(
                                                'ipaddress' => $value['ipaddress'],
                                                'reason'    => 'Not Responding'
                                            );
                                        }
                                    }

                                    if( empty( $removed ) == false )
                                    {

                                        $addressdatabase->deleteMultipleAddresses( $removed, $session->getSessionUser() );

                                        ?>
                                        <div class="panel panel-danger">
                                            <div class="panel-heading">
                                                Attention!
                                            </div>
                                            <div class="panel-body">

                                                <?php

                                                    foreach( $removed as $value )
                                                    {

                                                        ?>

                                                        <p>
                                                            IP [<?=$value['ipaddress']?>] Removed < <?=$value['reason']?> >
                                                        </p>
                                                        <?php
                                                    }
                                                ?>
                                            </div>
                                        </div>
                                        <?php
                                    }

                                    $addresses = array_reverse( $addressdatabase->getUserAddresses( $session->getSessionUser() ) );

                                    foreach( $addresses as $key=>$value )
                                    {

                                        Flight::render('syscrack/templates/template.address', array('key' => $key, 'value' => $value, 'computers' => $computer, 'internet' => $internet, 'viruses' => $viruses ) );
                                    }
                                }
                            }
                        ?>
                    </div>
                </div>
            </div>

            <?php

                Flight::render('syscrack/templates/template.footer', array('breadcrumb' => true ) );
            ?>
        </div>
    </body>
</html>