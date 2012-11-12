<?php
/*    Catroid: An on-device graphical programming language for Android devices
 *    Copyright (C) 2010-2012 The Catroid Team
 *    (<http://code.google.com/p/catroid/wiki/Credits>)
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU Affero General Public License as
 *    published by the Free Software Foundation, either version 3 of the
 *    License, or (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

abstract class CorePresenterCommon {
    protected $module;
    protected $xmlSerializerOptions;
    public $isMobile;

    public function __construct(CoreModule $module) {
      $this->module = $module;
      $this->isMobile = $this->module->clientDetection->isMobile();
      $this->xmlSerializerOptions = "";
        
      foreach($this->module->getData() as $key => $value) {
        if(gettype($value) == 'object') {
          $this->module->unsetData($key);
        }
        if($key == 'xmlSerializerOptions') {
          $this->xmlSerializerOptions = $value;
          $this->module->unsetData($key);
        }
      }
    }

    abstract public function display();
    
    public function getCss() {
      return $this->module->getCss();
    }
    
    public function getJs() {
      return $this->module->getJs();
    }
    public function getGlobalJs() {
      return $this->module->getGlobalJs();
    }
  
    public function getWebsiteTitle() {
      return $this->module->getWebsiteTitle();
    }
    
    public function __destruct() {
    }
}

?>