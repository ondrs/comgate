<?php

class AgmoPaymentsSimpleDatabase {

    private $_dataFolderName;
    private $_merchant;
    private $_test;

    /**
     * @param string $dataFolderName
     *      folder name where to save data
     * @param string $merchant
     *      merchants identifier
     * @param boolean $test
     *      TRUE = testing system variant
     *      FALSE = release (production) system variant
     */
    public function __construct($dataFolderName, $merchant, $test) {
        $this->_dataFolderName = $dataFolderName;
        $this->_merchant = $merchant;
        $this->_test = $test;
    }

    /**
     * returns next numeric identifier for a merchant transaction
     *
     * @return int
     * @throws Exception
     */
    public function createNextRefId() {
        $refId = 1;
        $fileName = $this->_dataFolderName.'/agmo_payments_counter_'.$this->_merchant.'.txt';
        $fileContents = @file_get_contents($fileName);
        if ($fileContents !== false) {
            $refId = intval($fileContents, 10) + 1;
        }
        if (@file_put_contents($fileName, $refId) === false) {
            $error = error_get_last();
            throw new Exception('Cannot write to file: '.$fileName."\n\n".$error['message']);
        }
        return $refId;
    }

    /**
     * store the transaction data in a data file
     *
     * @param string $transId
     * @param string $refId
     * @param float $price
     * @param string $currency
     * @param string $status
     * @param string $fee
     *
     * @throws Exception
     */
    public function saveTransaction($transId, $refId, $price, $currency, $status, $fee = 'unknown') {
        $fileName = $this->_dataFolderName.'/agmo_payment_'.$this->_merchant.'_'.$refId.'_'.$transId.'.txt';
        $fileData = array(
            'test' => $this->_test,
            'price' => $price,
            'curr' => $currency,
            'status' => $status,
            'fee' => $fee
        );
        if (@file_put_contents($fileName, json_encode($fileData)) === false) {
            $error = error_get_last();
            throw new Exception('Cannot write to file: '.$fileName."\n\n".$error['message']);
        }
    }

    /**
     * returns transaction status from a data file
     *
     * @param string $transId
     * @param string $refId
     *
     * @return string
     * @throws Exception
     */
    public function getTransactionStatus($transId, $refId) {
        $fileName = $this->_dataFolderName.'/agmo_payment_'.$this->_merchant.'_'.$refId.'_'.$transId.'.txt';
        if (!file_exists($fileName)) {
            throw new Exception('Unknown transaction');
        }
        $fileData = json_decode(file_get_contents($fileName), true);
        return $fileData['status'];
    }

    /**
     * checks transaction parameters in a data file
     *
     * @param string $transId
     * @param string $refId
     * @param float $price
     * @param string $currency
     *
     * @throws Exception
     */
    public function checkTransaction($transId, $refId, $price, $currency) {
        $fileName = $this->_dataFolderName.'/agmo_payment_'.$this->_merchant.'_'.$refId.'_'.$transId.'.txt';
        if (!file_exists($fileName)) {
            throw new Exception('Unknown transaction');
        }
        $fileData = json_decode(file_get_contents($fileName), true);
        if (
            $fileData['test']  !== $this->_test ||
            $fileData['price'] !== $price ||
            $fileData['curr']  !== $currency
        ) {
            throw new Exception('Invalid payment parameters');
        }
    }

}