<?php
namespace Application\Form\Lpa;

use Zend\Form\Form;
use Zend\Form\Element\Csrf;
use Zend\Form\Exception;
use Zend\InputFilter\InputFilterInterface;
use Opg\Lpa\DataModel\Validator\ValidatorResponse;
use Zend\Form\Element\Checkbox;
use Zend\Form\FormInterface;
use Zend\Form\Element\Radio;

abstract class AbstractForm extends Form
{
    protected $inputFilter;
    
    public function __construct($formName)
    {
        parent::__construct($formName);
        
        $this->setAttribute('method', 'post');

        $this->add( (new Csrf('secret'))->setCsrfValidatorOptions([
            'timeout' => null,
            'salt' => sha1('Application\Form\Lpa-Salt'),
        ]));
        
        foreach($this->formElements as $name => $elm) {
            $params = [
                    'name' => $name,
                    'type' => $elm['type'],
            ];
            
            if(array_key_exists('options', $elm)) {
                $params['options'] = $elm['options'];
            }
                    
            if(array_key_exists('attributes', $elm)) {
                $params['attributes'] = $elm['attributes'];
            }
            
            $this->add($params);
        }
    }
    
    /**
     * (non-PHPdoc)
     * 
     * Validate form elements through model validation.
     * 
     * @see \Zend\Form\Form::isValid()
     */
    public function isValid()
    {
        if ($this->hasValidated) {
            return $this->isValid;
        }

        $this->isValid = false;

        if (!is_array($this->data) && !is_object($this->object)) {
            throw new Exception\DomainException(sprintf(
                '%s is unable to validate as there is no data currently set',
                __METHOD__
            ));
        }

        if (!is_array($this->data)) {
            $data = $this->extract();
            $this->populateValues($data, true);
            if (!is_array($data)) {
                throw new Exception\DomainException(sprintf(
                    '%s is unable to validate as there is no data currently set',
                    __METHOD__
                ));
            }
            $this->data = $data;
        }

        $filter = $this->getInputFilter();
        if (!$filter instanceof InputFilterInterface) {
            throw new Exception\DomainException(sprintf(
                '%s is unable to validate as there is no input filter present',
                __METHOD__
            ));
        }

        $filter->setData($this->data);
        $filter->setValidationGroup(InputFilterInterface::VALIDATE_ALL);

        $validationGroup = $this->getValidationGroup();
        if ($validationGroup !== null) {
            $this->prepareValidationGroup($this, $this->data, $validationGroup);
            $filter->setValidationGroup($validationGroup);
        }
        
        // validate data though model validators.
        $modelValidationResult = $this->validateByModel();
        
        // take both Zend validation and model validation result into account 
        $this->isValid = $result = (bool) ($filter->isValid() & $modelValidationResult['isValid']);
        
        $this->hasValidated = true;
        
        if ($result && $this->bindOnValidate()) {
            $this->bindValues();
        }

        if (!$result) {
            // merge Zend and model validation errors.
            $this->setMessages(array_merge($filter->getMessages(), $modelValidationResult['messages']));
        }

        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * 
     * Setup common filters for all elements of a form.
     * 
     * @see \Zend\Form\Form::getInputFilter()
     */
    public function getInputFilter()
    {
        $inputFilter = parent::getInputFilter();

        foreach($this->formElements as $name => $elm) {
            $params = [
                    'name' => $name,
                    'required' => false,
            ];
            
            if(array_key_exists('required', $elm)) {
                $params['required'] = $elm['required'];
            }
            
            // if 'filters' is not set in a form class, add the default filters - StripTags and StringTrim,
            // if 'filters' is set in a form class and is not false, merge filters with the default ones.
            // if 'filters; is set in a form class and is false, filtering is disabled.
            if(!array_key_exists('filters', $elm)) {
                $elm['filters']  = [
                        ['name' => 'StripTags'],
                        ['name' => 'StringTrim'],
                ];
            }
            elseif($elm['filters'] !== false) {
                $elm['filters']  = [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ];
            
                array_merge($params['filters'], $elm['filters']);
            }
            
            $inputFilter->add($params);
        }

        $this->inputFilter = $inputFilter;
    
        return $this->inputFilter;
    }
    
    /**
     * Convert model validation response to Zend Form validation messages format.
     * 
     * @param ValidatorResponse $validationResponse: e.g. {storage: ['name.first'=>['value'=>'', 'messages'=>[0=>'cannot-be-blank'],],]}
     * @return array: e.g. ['name-first'=>'cannot-be-blank',]
     * or
     * @param ValidatorResponse $validationResponse: e.g. {storage: ['address.address2/postcode'=>['value'=>'', 'messages'=>[0=>'cannot-be-null'],],]}
     * @return array: e.g. ['address-address2'=>'linked-1-cannot-be-null','address-postcode'=>'linked-1-cannot-be-null',]
     */
    protected function modelValidationMessageConverter(ValidatorResponse $validationResponse, $context=null)
    {
        $messages = [];
        $linkIdx = 1;
        
        // loop through all form elements.
        foreach($validationResponse as $validationErrorKey => $validationErrors) {
            
            $errorKeyStubs = explode('.', $validationErrorKey);
            
            // set the first stub of a form element name
            $formElementName = $errorKeyStubs[0];
            
            // store multi fields element names which they are validated together.
            $multiFieldsNames = [];
            
            // loop through stubs.
            for($i=1; $i<count($errorKeyStubs); $i++) {
                
                // test if it's a multi-fields validation error.
                if(strstr($errorKeyStubs[$i], '/')) {
                    $linkedElements = explode('/', $errorKeyStubs[$i]);
                    $prefix = 'linked-'.$linkIdx++.'-';
                    $multiFieldsNames[$prefix] = [];
                    
                    // store multi fields element names
                    foreach($linkedElements as $name) {
                        $multiFieldsNames[$prefix][] = $formElementName.'-'.$name;
                    }
                }
                else {
                    $formElementName.='-'.$errorKeyStubs[$i];
                }
            }
            
            if(count($multiFieldsNames) > 0) {
                // store validations errors for multi fields elements.
                foreach($multiFieldsNames as $prefix=>$multiFields) {
                    foreach($multiFields as $name) {
                        $messages[$name] = $validationErrors['messages'];
                        $messages[$name][0] = $prefix.$messages[$name][0];
                    }
                }
            }
            else {
                $messages[$formElementName] = $validationErrors['messages'];
            }
        }
        
        return $messages;
    }
    
    /**
     * Convert form data to model-compatible input data format.
     * 
     * @param array $formData. e.g. ['name-title'=>'Mr','name-first'=>'John',]
     * 
     * @return array. e.g. ['name'=>['title'=>'Mr','first'=>'John',],]
     */
    protected function formDataModelization($formData)
    {
        $modelData = [];
        foreach($formData as $key=>$value) {
            $names = explode('-', $key);
            $m = &$modelData;
            for($i=0; $i<count($names); $i++) {
                if(!array_key_exists($names[$i], $m)) {
                    $m[$names[$i]] = [];
                }
                $m = &$m[$names[$i]];
            }
            
            if($this->has($key) && (($this->get($key) instanceof Checkbox)||($this->get($key) instanceof Radio))) {
                // convert checkbox/radio value "" to false and "1" to true
                if($value == '') {
                    $m = false;
                }
                elseif($value === '1') {
                    $m = true;
                }
                else {
                    $m = $value;
                }
            }
            else {
                $m = $value;
            }
        }
        
        return $modelData;
    }
    
    public function bind($modelizedDataArray, $flags = FormInterface::VALUES_NORMALIZED)
    {
        return parent::bind(new \ArrayObject($modelizedDataArray));
    }
    
    /**
     * Convert form data to model-compatible input data format.
     * 
     * @return \Application\Form\Lpa\array.
     */
    public function getModelizedData()
    {
        if($this->data != null) {
            return $this->formDataModelization($this->data);
        }
    }
    
    abstract protected function validateByModel();
}
