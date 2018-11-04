<?php

namespace earx\backup\dropbox;

use Yii;
use yii\helpers\Console;
use yii\console\Controller;
use yii\base\InvalidConfigException;

use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\Dropbox;

/**
 * Console command for making backup and upload them to Dropbox
 *
 * @property \demi\backup\Component $component
 * @property Dropbox $client
 */
class BackupController extends Controller
{
    /** @var string Name of \demi\backup\Component in Yii components. Default Yii::$app->backup */
    public $backupComponent = 'backup';
    /** @var string Dropbox app identifier. https://www.dropbox.com/developers/apps */
    public $dropboxAppKey;
    /** @var string Dropbox app secret. https://www.dropbox.com/developers/apps */
    public $dropboxAppSecret;
    /**
     * Dropbox access token for user which will be get up backups.
     *
     * To get this navigate to https://www.dropbox.com/developers/apps/info/<AppKey>
     * and press OAuth 2: Generated access token button.
     *
     * @var string
     */
    public $dropboxAccessToken;
    /** @var string Path in the bropbox where would be saved backups */
    public $dropboxUploadPath = '/';
    /** @var bool if TRUE: will be deleted files in the dropbox where $expiryTime has come */
    public $autoDelete = true;
    /**
     * Number of seconds after which the file is considered deprecated and will be deleted.
     * By default 1 month (2592000 seconds).
     *
     * @var int
     */
    public $expiryTime = 2592000;

    /** @var Dropbox Dropbox client instance */
    protected $_client;

    /** @var Dropbox Dropbox client instance */
    protected $_app;

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        if ($this->dropboxAppKey === null) {
            throw new InvalidConfigException('You must set "\demi\dropbox\backup\BackupController::$dropboxAppKey"');
        } elseif ($this->dropboxAppSecret === null) {
            throw new InvalidConfigException('You must set "\demi\dropbox\backup\BackupController::$dropboxAppSecret"');
        } elseif ($this->dropboxAccessToken === null) {
            throw new InvalidConfigException('You must set "\demi\dropbox\backup\BackupController::$dropboxAccessToken"');
        }

        parent::init();
    }

    /**
     * Run creating new backup and save it to the Dropbox
     */
    public function actionIndex()
    {
        // Make new backup
        $backupFile = $this->component->create();
        // Get name for new dropbox file
        $dropboxFileName = basename($backupFile);

        $dropboxFile = new \Kunnu\Dropbox\DropboxFile($backupFile);

        $file = $this->client->upload($dropboxFile, $this->dropboxUploadPath . $dropboxFileName, ['autorename' => true]);

        $this->stdout('Backup file successfully uploaded into dropbox: ' . $dropboxFileName . PHP_EOL, Console::FG_GREEN);

        if ($this->autoDelete) {
            // Auto removing files from dropbox that oldest of the expiry time
            $this->actionDeleteJunk();
        }

        // Cleanup server backups
        $this->component->deleteJunk();
    }

    /**
     * Removing files from dropbox that oldest of the expiry time
     *
     * @throws DropboxClientException
     */
    public function actionDeleteJunk()
    {
        // Get all files from dropbox backups folder
        // Calculate expired time
        $expiryDate = time() - $this->expiryTime;

        /** @var \Kunnu\Dropbox\ModelCollection $items */
        $items = $this->client->listFolder($this->dropboxUploadPath)->getItems();


        /** @var \Kunnu\Dropbox\Models\FileMetadata $items */
        foreach ($items as $item) {


            $filepath = $item->getDataProperty('name');
            // Dropbox file last modified time
            $filetime = strtotime($item->getDataProperty('server_modified'));

            if (substr($filepath, -4) !== '.tar') {
                // Check extension
                continue;
            }

            if ($filetime <= $expiryDate) {

                // if the time has come - delete file
                $this->client->delete($this->dropboxUploadPath.$filepath);
                $this->stdout('expired file was deleted from dropbox: ' . $filepath . PHP_EOL, Console::FG_YELLOW);
            }
        }
    }

    /**
     * Get instance of Dropbox client
     *
     * @return DropboxApp
     */
    public function getApp()
    {
        if ($this->_app instanceof DropboxApp) {
            return $this->_app;
        }

        $this->_app = new DropboxApp($this->dropboxAppKey, $this->dropboxAppSecret, $this->dropboxAccessToken);

        return $this->_app;
    }


    /**
     * Get instance of Dropbox client
     *
     * @return Dropbox
     */
    public function getClient()
    {
        if ($this->_client instanceof Dropbox) {
            return $this->_client;
        }

        return $this->_client = $dropbox = new Dropbox($this->app);
    }

    /**
     * Get Backup component
     *
     * @return \demi\backup\Component
     * @throws \yii\base\InvalidConfigException
     */
    public function getComponent()
    {
        return Yii::$app->get($this->backupComponent);
    }
}
