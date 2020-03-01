<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * DeliveryServerPaswordHandlerBehavior
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.3.5.4
 * 
 */
 
class DeliveryServerPaswordHandlerBehavior extends CActiveRecordBehavior
{
    protected $_cipher;
    
    protected $_plainTextPassword;
    
    protected function getCipher()
    {
        if ($this->_cipher !== null) {
            return $this->_cipher;
        }
        $classes = array('Base', 'Rijndael', 'AES');
        foreach ($classes as $class) {
            if (!class_exists('Crypt_' . $class, false)) {
                require_once Yii::getPathOfAlias('common.vendors.PHPSecLib.Crypt.' . $class) . '.php';
            }
        }
        $this->_cipher = new Crypt_AES();
        $this->_cipher->setKeyLength(128);
        $this->_cipher->setKey('abcdefghqrstuvwxyz123456ijklmnop');
        return $this->_cipher;
    }
    
    public function beforeSave($event)
    {
        if (empty($this->owner->password)) {
            return;
        }
        $this->_plainTextPassword = $this->owner->password;
        $this->owner->password    = base64_encode($this->getCipher()->encrypt($this->owner->password));
    }
    
    public function afterSave($event)
    {
        if (empty($this->owner->password)) {
            return;
        }
        $this->owner->password = $this->_plainTextPassword;
    }
    
    public function afterFind($event)
    {
        if (empty($this->owner->password)) {
            return;
        }
        $password = base64_decode($this->owner->password, true);
        if (base64_encode($password) !== $this->owner->password) {
            return;
        }
        $this->owner->password = $this->getCipher()->decrypt($password);
    }
}