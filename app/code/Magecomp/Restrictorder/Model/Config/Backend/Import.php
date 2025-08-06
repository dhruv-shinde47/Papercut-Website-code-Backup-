<?php
namespace Magecomp\Restrictorder\Model\Config\Backend;

use Magento\Framework\Filesystem\DirectoryList;

class Import extends \Magento\Framework\App\Config\Value
{
	protected $_restrictionModel;
	protected $_dbConnection;
	protected $_messageManager;
	protected $_fileUploader;
    protected $_tmpDirectory;
	public function __construct(
    		\Magento\Framework\Model\Context $context,
    		\Magento\Framework\Registry $registry,
    		\Magento\Framework\App\Config\ScopeConfigInterface $config,
    		\Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
    		\Magecomp\Restrictorder\Model\RestrictionFactory $restrictionModel,
    		\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
    		\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
			\Magento\Framework\Filesystem $filesystem,
			\Magento\Framework\Filesystem\Directory\ReadFactory $readFactory,
			\Magento\Framework\App\ResourceConnection $connection,
			\Magento\Framework\Message\ManagerInterface $messageManager,
			\Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
    		$data = array()
    ) {
			$this->_restrictionModel = $restrictionModel;
			$this->_filesystem = $filesystem;
			$this->_readFactory = $readFactory;
			$this->_dbConnection = $connection;
			$this->_messageManager = $messageManager;
			$this->_fileUploader = $uploaderFactory;
            $this->_tmpDirectory = $filesystem->getDirectoryRead(DirectoryList::SYS_TMP);
			parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function beforeSave()
    {
        $value = $this->getValue();

        if (!empty($value['value'])) {
            $this->setValue($value['value']);
        }

        if (empty($value['tmp_name'])) {
            return $this;
        }

        $tmpPath = $this->_tmpDirectory->getRelativePath($value['tmp_name']);

        if ($tmpPath && $this->_tmpDirectory->isExist($tmpPath)) {
            if (!$this->_tmpDirectory->stat($tmpPath)['size']) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The uploaded file is empty.'));
            }
            $this->setValue($value['name']);

            $stream = $this->_tmpDirectory->openFile($tmpPath);
            $headers = $stream->readCsv();

            if ($headers === false || count($headers) < 2) {
                $stream->close();
                throw new \Magento\Framework\Exception\LocalizedException(__('Please Upload Correct CSV File Format.'));
            }
            $arrayColumn = 0 ;
            $twodarray = array();

            while (false !== ($csvLine = $stream->readCsv())) {
                $twodarray[$arrayColumn][0] = $csvLine[0];
                $twodarray[$arrayColumn][1] = $csvLine[1];
                $twodarray[$arrayColumn][2] = $csvLine[2];
                $arrayColumn++;
            }

            $totalRow = $this->_saveImportData($twodarray,$arrayColumn);
            $stream->close();
            $this->_messageManager->addSuccess(__("Successfully Imported $totalRow Restriction."));
        }
        return $this;
    }

	public function _saveImportData($data,$arrayColumn)
	{
		try
		{
			$connection = $this->_dbConnection->getConnection();
			$connection->beginTransaction();
			$tableName = $this->_dbConnection->getTableName('magecomp_restrictorder');
			$connection->delete($tableName);
			$connection->commit();
			$totalRow = 0 ;
			for($i=0;$i < $arrayColumn ; $i++)
			{
				$codModel = $this->_restrictionModel->create();
				$codModel->setCountryId($data[$i][0]);
				$codModel->setCity($data[$i][1]);
				$codModel->setZipCode($data[$i][2]);
				$codModel->save();
				$totalRow++;
			}
			return $totalRow;
		}
		catch(\Exception $e)
		{
			 $this->_logger->info($e->getMessage());
		}
	}
}
