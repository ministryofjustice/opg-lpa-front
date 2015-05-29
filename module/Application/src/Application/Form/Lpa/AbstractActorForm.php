<?php
namespace Application\Form\Lpa;

abstract class AbstractActorForm extends AbstractForm
{
    /**
     * An actor model object is a Donor, Human, TrustCorporation, CertificateProvider, PeopleToNotify model object.
     * @var Opg\Lpa\DataModel\AbstractData $actor
     */
    protected $actorModel;
    
   /**
    * Validate form input data through model validators.
    * 
    * @return [isValid => bool, messages => [<formElementName> => string, ..]]
    */
    public function validateByModel()
    {
        $dataForModel = $this->convertFormDataForModel($this->data);
    
        $messages = [];
        if(array_key_exists('dob', $dataForModel)) {
            list($year, $month, $day) = explode('-', $dataForModel['dob']['date']);
            if(!checkdate($month, $day, $year)) {
                $messages['dob-date-year'] = ['invalid date'];
                unset($dataForModel['dob']);
            }
        }
    
        $this->actorModel->populate($dataForModel);
        $validation = $this->actorModel->validate();
    
        // set validation message for form elements
        if($validation->offsetExists('dob')) {
            $validation['dob-date-year'] = $validation['dob'];
            unset($validation['dob']);
        }
        elseif($validation->offsetExists('dob.date')) {
            $validation['dob-date-year'] = $validation['dob.date'];
            unset($validation['dob.date']);
        }
    
        if(array_key_exists('email', $dataForModel) && ($dataForModel['email'] == null) && $validation->offsetExists('email')) {
            $validation['email-address'] = $validation['email'];
            unset($validation['email']);
        }
    
        if(array_key_exists('phone', $dataForModel) && ($dataForModel['phone'] == null) && $validation->offsetExists('phone')) {
            $validation['phone-number'] = $validation['phone'];
            unset($validation['phone']);
        }
    
        if(array_key_exists('name', $dataForModel) && ($dataForModel['name'] == null) && $validation->offsetExists('name')) {
            if(array_key_exists('name-first', $this->data)) {
                $validation['name-first'] = $validation['name'];
                $validation['name-last']  = $validation['name'];
                unset($validation['name']);
            }
        }
    
        if(empty($message) && (count($validation) == 0)) {
            return ['isValid'=>true, 'messages' => []];
        }
        else {
            return [
                    'isValid'=>false,
                    'messages' => array_merge($this->modelValidationMessageConverter($validation), $messages),
            ];
        }
    }
    
    /**
     * Convert form data to model-compatible input data format.
     *
     * @param array $formData. e.g. ['name-title'=>'Mr','name-first'=>'John',]
     *
     * @return array. e.g. ['name'=>['title'=>'Mr','first'=>'John',],]
     */
    protected function convertFormDataForModel($formData)
    {
        if(array_key_exists('dob-date-day', $formData)) {
            if(($formData['dob-date-year']>0) && ($formData['dob-date-month']>0) && ($formData['dob-date-day']>0)) {
                $formData['dob-date'] = $formData['dob-date-year'] . '-' . $formData['dob-date-month'] . '-' . $formData['dob-date-day'];
                unset($formData['dob-date-day'],$formData['dob-date-month'],$formData['dob-date-year']);
            }
            else {
                $formData['dob'] = null;
            }
        }
        
        $dataForModel = parent::convertFormDataForModel($formData);
        
        if(isset($dataForModel['email']) && ($dataForModel['email']['address'] == "")) {
            $dataForModel['email'] = null;
        }
        
        if(isset($dataForModel['phone']) && ($dataForModel['phone']['number'] == "")) {
            $dataForModel['phone'] = null;
        }
        
        if(isset($dataForModel['name']) && is_array($dataForModel['name']) && ($dataForModel['name']['first'] == "") && ($dataForModel['name']['last'] == "")) {
            $dataForModel['name'] = null;
        }
        
        return $dataForModel;
    }
}
