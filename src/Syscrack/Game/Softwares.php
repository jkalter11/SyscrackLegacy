<?php
namespace Framework\Syscrack\Game;

/**
 * Lewis Lancaster 2017
 *
 * Class Softwares
 *
 * @package Framework\Syscrack\Game
 */

use Framework\Application\Settings;
use Framework\Application\Utilities\FileSystem;
use Framework\Application\Utilities\Factory;
use Framework\Exceptions\SyscrackException;
use Framework\Database\Tables\Softwares as Database;
use Framework\Syscrack\Game\Structures\Software as Structure;

class Softwares
{

    /**
     * @var Factory
     */

    protected $factory;

    /**
     * @var Database
     */

    protected $database;

    /**
     * Softwares constructor.
     *
     * @param bool $autoload
     */

    public function __construct( $autoload=true )
    {

        $this->factory = new Factory( Settings::getSetting('syscrack_software_namespace') );

        $this->database = new Database();

        if( $autoload == true )
        {

            $this->loadSoftwares();
        }
    }

    /**
     * Gets the software from the database
     *
     * @param $softwareid
     *
     * @return mixed|null
     */

    public function getDatabaseSoftware( $softwareid )
    {

        return $this->database->getSoftware( $softwareid );
    }

    /**
     * Returns true if this softwareid exists
     *
     * @param $softwareid
     *
     * @return bool
     */

    public function softwareExists( $softwareid )
    {

        if( $this->database->getSoftware( $softwareid ) == null )
        {

            return false;
        }

        return true;
    }

    /**
     * Gets the software class related to this software id
     *
     * @param $softwareid
     *
     * @return null
     */

    public function getSoftwareClassFromID( $softwareid )
    {

        return $this->findSoftwareByUniqueName( $this->database->getSoftware( $softwareid )->uniquename );
    }

    /**
     * Creates a new piece of software
     *
     * @param $software
     *
     * @param $userid
     *
     * @param $computerid
     *
     * @return int
     */

    public function createSoftware( $software, string $softwarename, int $userid, int $computerid )
    {

        if( $this->hasSoftware( $software ) == false )
        {

            throw new SyscrackException();
        }

        $class = $this->getSoftwareClass( $software );

        if( $class instanceof Structure == false )
        {

            throw new SyscrackException();
        }

        $configuration = $class->configuration();

        $array = array(
            'userid'        => $userid,
            'computerid'    => $computerid,
            'level'         => $class->getDefaultLevel(),
            'size'          => $class->getDefaultSize(),
            'uniquename'    => $configuration['uniquename'],
            'type'          => $configuration['type'],
            'softwarename'  => $softwarename,
            'lastmodified'  => time(),
            'installed'     => false
        );

        return $this->database->insertSoftware( $array );
    }

    /**
     * Finds a software by its unqiue name
     *
     * @param $uniquename
     *
     * @return null
     */

    public function findSoftwareByUniqueName( $uniquename )
    {

        $classes = $this->factory->getAllClasses();

        foreach( $classes as $class )
        {

            if( $class instanceof Structure == false )
            {

                throw new SyscrackException();
            }

            /** @var Structure $class */
            if( $class->configuration()['uniquename'] == $uniquename )
            {

                return $class;
            }
        }

        return null;
    }

    /**
     * Gets the software class, which is used when processing what a software actually does
     *
     * @param $software
     *
     * @return Structure
     */

    public function getSoftwareClass( $software )
    {

        return $this->factory->findClass( $software );
    }

    /**
     * Gets the softwares type
     *
     * @param $software
     */

    public function getSoftwareType( $software )
    {

        return $this->getSoftwareClass( $software )->configuration()['type'];
    }

    /**
     * Gets the softwares name
     *
     * @param $software
     */

    public function getSoftwareName( $software )
    {

        return $this->getSoftwareClass( $software )->configuration()['name'];
    }

    /**
     * Gets the software extension
     *
     * @param $software
     */

    public function getSoftwareExtension( $software )
    {

        return $this->getSoftwareClass( $software )->configuration()['extension'];
    }

    /**
     * Gets the softwares unique name
     *
     * @param $software
     *
     * @return mixed
     */

    public function getSoftwareUniqueName( $software )
    {

        return $this->getSoftwareClass( $software )->configuration()['unqiuename'];
    }

    /**
     * Gets the softwares default file size on the users system
     *
     * @param $software
     *
     * @return float
     */

    public function getSoftwareDefaultSize( $software )
    {

        return $this->getSoftwareClass( $software )->getDefaultSize();
    }

    /**
     * Gets the softwares default level
     *
     * @param $software
     *
     * @return float
     */

    public function getSoftwareDefaultLevel( $software )
    {

        return $this->getSoftwareClass( $software )->getDefaultLevel();
    }

    /**
     * Calls a method inside the software class
     *
     * @param $software
     *
     * @param string $method
     *
     * @param array $parameters
     *
     * @return mixed
     */

    public function classSoftwareMethod( $software, $method='onExecuted', array $parameters )
    {

        $software = $this->getSoftwareClass( $software );

        if( $software instanceof Structure == false )
        {

            throw new SyscrackException();
        }

        if( empty( $parameters ) == false )
        {

            return call_user_func_array( array( $software, $method), $parameters );
        }

        return $software->{ $method };
    }

    /**
     * Returns true if we have this software
     *
     * @param $software
     *
     * @return bool
     */

    public function hasSoftware( $software )
    {

        if( $this->factory->hasClass( $software ) == false )
        {

            return false;
        }

        return true;
    }

    /**
     * Loads all the software classes into the factory
     */

    private function loadSoftwares()
    {

        $softwares = FileSystem::find( Settings::getSetting('syscrack_software_location') );

        foreach( $softwares as $software )
        {

            $this->factory->createClass( $software );
        }
    }

}