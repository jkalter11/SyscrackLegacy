<?php
namespace Framework\Syscrack\Game;

/**
 * Lewis Lancaster 2017
 *
 * Class Finance
 *
 * @package Framework\Syscrack\Game
 */

use Framework\Application\Settings;
use Framework\Database\Tables\Computers;
use Framework\Database\Tables\Banks;
use Framework\Exceptions\SyscrackException;

class Finance
{

    /**
     * @var Computers
     */

    protected $computers;

    /**
     * @var Banks
     */

    protected $banks;

    /**
     * Finance constructor.
     */

    public function __construct()
    {

        $this->computers = new Computers();

        $this->banks = new Banks();
    }

    /**
     * Gets the users cash at the specified bank
     *
     * @param $userid
     *
     * @param $computerid
     *
     * @return int
     */

    public function getUserCash( $userid, $computerid )
    {

        if( $this->isBank( $computerid ) == false )
        {

            throw new SyscrackException();
        }

        $account = $this->getAccountAtBank( $userid, $computerid );

        if( $account == null )
        {

            throw new SyscrackException();
        }

        return $account->cash;
    }

    /**
     * Gets all the computers who are banks
     *
     * @return mixed|null
     */

    public function getBanks()
    {

        return $this->computers->getComputerByType( Settings::getSetting('syscrack_bank_type') );
    }

    /**
     * Gets the users account at the specified bank
     *
     * @param $userid
     *
     * @param $computerid
     *
     * @return mixed|null
     */

    public function getAccountAtBank( $userid, $computerid )
    {

        $accounts = $this->banks->getAccountsOnComputer( $computerid );

        foreach( $accounts as $account )
        {

            if( $account->userid == $userid )
            {

                return $account;
            }
        }

        return null;
    }

    /**
     * Gets the users bank account
     *
     * @param $userid
     *
     * @return \Illuminate\Support\Collection|null
     */

    public function getUserBankAccounts( $userid )
    {

        return $this->banks->getUserAccounts( $userid );
    }

    /**
     * Gets the account by its account number
     *
     * @param $accountnumber
     *
     * @return \Illuminate\Support\Collection|null
     */

    public function getByAccountNumber( $accountnumber )
    {

        return $this->banks->getByAccountNumber( $accountnumber );
    }

    /**
     * Returns true if the account number exists
     *
     * @param $accountnumber
     *
     * @return bool
     */

    public function accountNumberExists( $accountnumber )
    {

        if( $this->banks->getByAccountNumber( $accountnumber ) == null )
        {

            return false;
        }

        return true;
    }

    /**
     * Returns true if the user has an account
     *
     * @param $userid
     *
     * @return bool
     */

    public function hasAccount( $userid )
    {

        if( $this->banks->getUserAccounts( $userid ) == null )
        {

            return false;
        }

        return true;
    }

    /**
     * @param $computerid
     *
     * @param $userid
     *
     * @return int
     */

    public function createAccount( $computerid, $userid )
    {

        if( $this->getAccountAtBank( $userid, $computerid ) !== null )
        {

            throw new SyscrackException();
        }

        return $this->banks->insertAccount( array(
            'computerid'        => $computerid,
            'userid'            => $userid,
            'accountnumber'     => $this->getAccountNumber(),
            'cash'              => Settings::getSetting('syscrack_bank_default_balance')
        ));
    }

    /**
     * Deposits ( adds ) money into an account
     *
     * @param $userid
     *
     * @param $computerid
     *
     * @param $amount
     */

    public function deposit( $userid, $computerid, $amount )
    {

        $this->banks->updateAccount( $computerid, $userid, array(
            'cash' => $this->getUserCash( $userid, $computerid ) + $amount
        ));
    }

    /**
     * Withdraws ( takes ) money from a specified account
     *
     * @param $userid
     *
     * @param $amount
     *
     * @param $computerid
     */

    public function withdraw( $userid, $computerid, $amount )
    {

        $this->banks->updateAccount( $computerid, $userid, array(
            'cash' => $this->getUserCash( $userid, $computerid ) - $amount
        ));
    }

    /**
     * Returns true if the user has enough cash to afford this transaction
     *
     * @param $userid
     *
     * @param int $amount
     *
     * @param $computerid
     *
     * @return bool
     */

    public function canAfford( $userid, int $amount, $computerid )
    {

        $cash = $this->getUserCash( $userid, $computerid );

        if( $cash - $amount > 0 )
        {

            return true;
        }

        return false;
    }

    /**
     * Gets the account number
     *
     * @return int|string
     */

    private function getAccountNumber()
    {

        $number = 0;

        for ($i = 0; $i < Settings::getSetting('syscrack_bank_accountnumber_length'); $i++)
        {

            $number = $number . rand(0,9);
        }

        return $number;
    }

    /**
     * Returns true if the comptuer id is a bank
     *
     * @param $computerid
     *
     * @return bool
     */

    private function isBank( $computerid )
    {

        if( $this->computers->getComputer( $computerid )->type != Settings::getSetting('sysscrack_bank_type') )
        {

            return false;
        }

        return true;
    }
}