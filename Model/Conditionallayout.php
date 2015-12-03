<?php
/*
 *  Rolf van der Kaaden
 */

class Ophirah_Qquoteadv_Model_Conditionallayout
{

    private $layout = NULL;
    private $current_reference = NULL;

    public function observe($observer){
        $layout = $observer->getEvent()->getLayout()->getUpdate();
        $xml = simplexml_load_string('<?xml version="1.0"?><document>'.$layout->asString().'</document>');

        $this->layout = $layout;

        foreach($xml->reference as $reference){
            $referenceAttributes = $reference->attributes();
            if( isset( $referenceAttributes->name ) ) {
                $this->current_reference = $referenceAttributes["name"];
                $this->checkXml($reference);
            }
        }

        return;
    }

    protected function checkXml($reference) {
        if( isset($reference->action) ) {
            $this->checkConditions($reference->action);
        } elseif( isset($reference->block) ) {
            foreach($reference->block as $block) {
                $this->checkXml($block);
            }
        }
    }

    protected function checkConditions($actions){
        foreach ($actions as $action) {
            foreach($action->attributes() as $attribute => $value){
                if($attribute == 'ifmodel'){
                    $this->ifModel($value);
                } elseif($attribute == 'ifhelper'){
                    $this->ifHelper($value);
                }
            }
        }
    }

    //'helpername:function'
    protected function ifHelper($helperReference){
        $helper = explode(':', $helperReference);
        if(array_key_exists(1, $helper)){
            $condition = Mage::helper($helper[0])->$helper[1]();
            if(!$condition){
                $this->removeAction("ifhelper=\"".$helperReference."\"");
            }
        }
    }

    //'package_module/modelname:function'
    protected function ifModel($modelReference){
        $model = explode(':', $modelReference);
        if(array_key_exists(1, $helper)){
            $condition = Mage::getModel($model[0])->$model[1]();
            if(!$condition){
                $this->removeAction("ifmodel=\"".$modelReference."\"");
            }
        }
    }

    protected function removeAction($action){
        $buffer = $this->layout->asArray();
        $this->layout->resetUpdates();
        foreach($buffer as $update){
            if (strpos($update, $action) !== false) {
                $xmlActions = explode('<action ', $update);
                foreach($xmlActions as $xmlAction){
                    if (strpos($xmlAction, $action) !== false) {
                        $end = strpos($xmlAction, "/>")+2;
                        $firstLineBreak = strpos($xmlAction, "\n");
                        if($firstLineBreak === false || $end > $firstLineBreak) {
                            $end = strpos($xmlAction, "/action>")+8;
                        }
                        $remove = '<action '.substr($xmlAction, 0, $end);
                        $update = str_replace($remove, "", $update);
                    }
                }
            }
            $this->layout->addUpdate($update);
        }
    }

}
