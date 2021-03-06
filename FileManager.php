<?php namespace ecom\file;
/**
 * FileManager class file.
 *
 * @author Jin Hu <bixuehujin@gmail.com>
 */

use Yii;
use ecom\file\model\FileManaged;

/**
 * FileManager application component.
 *
 * Usage example:
 * ```
 * $fileManaged = Yii::app()->fileManager->createFileManaged('avatar');
 * $uploadedFile = CUploadedFile::getInstanceByName('file');
 * if (!($newFile = $fileManaged->upload($uploadedFile, FileManaged::TEMP_FILE))) {
 *   echo $fileManged->getUploadError()
 * } else {
 * 	 //some stuff with $newFile
 * }
 *
 * ```
 * @property string $basePath
 * @property array $domains
 */
class FileManager extends \CApplicationComponent
{
    private $_basePath;
    private $_domains;

    public $managedClass = 'ecom\file\model\FileManaged';

    /**
     * Sets the base path where all files should be stored in.
     *
     * @param  string                  $path
     * @return \ecom\files\FileManager
     * @throws \CException
     */
    public function setBasePath($path)
    {
        if ($path[0] != '/') {
            $path = \Yii::app()->getBasePath() . '/' . $path;
        }
        if (($realPath = realpath($path)) !== false && is_dir($realPath) && is_writable($realPath)) {
            $this->_basePath = $realPath;
        } else {
            throw new \CException("The path '$path' is invalid, make sure it is a valid directory and writable by your web process.");
        }
    }

    /**
     * Gets the base path where all files should be stored in.
     *
     * @return string
     */
    public function getBasePath()
    {
        if ($this->_basePath === null) {
            $path = Yii::app()->getBasePath() . '/../uploads';
            if (($realPath = realpath($path)) !== false && is_dir($realPath) && is_writable($realPath)) {
                $this->_basePath = $realPath;
            } else {
                throw new \CException("The path '$path' is invalid, make sure it is a valid directory and writable by your web process.");
            }
        }

        return $this->_basePath;
    }

    /**
     * Sets domain configure.
     *
     * @param array $domains
     *  array(
     *    'domainName' => array(
     *      'validateRule' => array( //config for CFileValidator
     *         'types' = array(),
     *            'mimeTypes' => array(),
     *            'minSize' => '',
     *            'maxSize' => '',
     *         ),
     *      'behaviors' => array(
     *
     *     ),
     *   ),
     * )
     * @return \ecom\files\FileManager
     */
    public function setDomains($domains)
    {
        $this->_domains = $domains;
    }

    /**
     * Gets all domain
     *
     * @return array
     */
    public function getDomains()
    {
        return $this->_domains;
    }

    /**
     * Get a single domain configuration.
     *
     * @param  string $domain The domain name
     * @return array
     */
    public function getDomain($domain)
    {
        $this->checkDomain($domain);

        return $this->_domains[$domain];
    }

    /**
     * Checking whether a domain is defined.
     *
     * @param  string  $domain
     * @return boolean
     */
    public function hasDomain($domain)
    {
        return isset($this->_domains[$domain]);
    }

    protected function checkDomain($domain)
    {
        if (!isset($this->_domains[$domain])) {
            throw new \CException("Domain '$domain' is not defined, please define it before using.");
        }
    }

    /**
     * Get the path of a domain.
     *
     * @param  string $domain
     * @return string
     */
    public function getPathOfDomain($domain)
    {
        $this->checkDomain($domain);

        return $this->getBasePath() . '/' . $domain;
    }

    public function getUrlOfDomain($domain)
    {
        $path = $this->getPathOfDomain($domain);
        $base = Yii::app()->getBasePath() . '/../';

        return str_replace(realpath($base), '', $path);
    }

    /**
     *
     * @param  string      $domain
     * @return FileManaged
     * @throws \CException
     */
    public function createManagedObject($domain)
    {
        $this->checkDomain($domain);

        $config      = array();
        $dominConfig = $this->domains[$domain];

        $config['domainBelongs'] = $domain;

        if (isset($dominConfig['validateRule'])) {
            $config['validateRule'] = $dominConfig['validateRule'];
        }
        $class = $this->managedClass;

        $object = new $class(null);

        foreach ($config as $name => $value) {
            $object->$name = $value;
        }

        $object->attachBehaviors($object->behaviors());

        //$object->attachBehaviors()
        return $object;
    }

    /**
     * Load a file from database.
     *
     * @param  integer     $fid
     * @return FileManaged
     */
    public function load($fid)
    {
        $class = $this->managedClass;

        return $class::load($fid);
    }

    /**
     * Load a file by its unique hash value.
     *
     * @param  string      $hash
     * @return FileManaged
     */
    public function loadByHash($hash)
    {
        $class = $this->managedClass;

        return $class::loadByHash($fid);
    }

    /**
     * Get the count of all attached files of specified entity.
     *
     * @param FileAttachable $entity
     * @param integer $usageType
     */
    public function getCountOfAttached(FileAttachable $entity, $usageType = FileAttachable::USAGE_TYPE_DEFAULT)
    {
        $class = $this->managedClass;

        return $class::fetchAttachedCountOf($entity, $usageType);
    }

    /**
     * Get all files attached to spefified entity and its type.
     *
     * @param FileAttachable $entity
     * @param unknown $type
     * @return FileManaged[]
     */
    public function getAllAttached(FileAttachable $entity, $type = FileAttachable::USAGE_TYPE_DEFAULT)
    {
        $class = $this->managedClass;

        return $class::fetchAllAttachedOf($entity, $type);
    }

    /**
     * Fetch files attached to spefified entity and its type.
     *
     * @param FileAttachable $entity
     * @param integer $type
     * @param integer $pageSize
     * @return \CActiveDataProvider
     */
    public function getAllAttachedProvider(FileAttachable $entity, $type = FileAttachable::USAGE_TYPE_DEFAULT, $pageSize = 20)
    {
        $class = $this->managedClass;

        return $class::fetchAttachedProviderOf($entity, $type, $pageSize);
    }

    /**
     * Returns whether the file is existed according hash.
     *
     * @return boolean
     */
    public function isFileExist($hash)
    {
        $class = $this->managedClass;

        return $class::model()->exist('hash=:hash', array(':hash' => $hash));
    }

    /**
     * Send a file to user.
     *
     * @param integer $id
     * @param boolean $terminate Whether terminate the application, defaults to true.
     */
    public function sendFile($fid, $terminate = true)
    {
        $file = static::load($fid);
        if (!$file) {
            throw new \CHttpException(404, 'File Not Found');
        }

        $path = $file->getRealPath();
        if (!file_exists($path)) {
            throw new \CHttpException(404, 'File Not Found');
        }

        Yii::app()->request->sendFile($file->name, file_get_contents($path), $file->mime, $terminate);
    }

    /**
     * Sends existing file to a browser as a download using x-sendfile.
     *
     * @param integer $fid
     * @param array   $options Extra options applied to Yii::app()->request->xSendFile()
     */
    public function xSendFile($fid, $options = array())
    {
        $file = static::load($fid);
        if (!$file) {
            throw new \CHttpException(404, 'File Not Found');
        }

        $path = $file->getRealPath();
        if (!file_exists($path)) {
            throw new \CHttpException(404, 'File Not Found');
        }

        $options += array(
            'saveName' => $file->name,
            'mimeType' => $file->mime,
        );
        Yii::app()->request->xSendFile($path, $options);
    }
}
