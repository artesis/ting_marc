<?php
class TingMarcxchangeRequest extends TingGenericRequest {
  protected $action = 'getObjectRequest';
  protected $resultClass = 'TingMarcxchangeResult';
  protected $rawResults = FALSE;

  public function __construct($url, $agency, $profile) {
    parent::__construct($url, $agency, $profile);
    $this->setParameter('objectFormat', 'marcxchange');
  }

  public function setId($id) {
    $this->setParameter('identifier', $id);
  }
}
