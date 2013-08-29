<?php
class TingMarcxchangeResult {
  /**
   * Raw data from webservice
   * @var OutputInterface
   */
  private $result;

  private $data = array();

  public function __construct(OutputInterface $result, TingMarcxchangeRequest $request) {
    $this->_position = 0;

    $this->result = $result;
    $this->process();
  }

  /**
   * Build items from raw data (json).
   */
  protected function process() {
    // Check for errors.
    $error = $this->result->getValue('searchResponse/error');
    if (!empty($error)) {
      throw new TingClientException($error);
    }

    $data = $this->result->getValue('searchResponse/result/searchResult');
    $data = reset($data);
    $data = $data->getValue('collection/object');
    $data = reset($data);
    $data = $data->getValue('collection/record/datafield');

    if (empty($data)) {
      unset($this->result);
      return;
    }

    $index = 0;
    foreach ($data as $datafield) {
      $tag = $datafield->getValue('@tag');

      $subfields = $datafield->getValue('subfield');

      if (empty($subfields)) {
        unset($this->result);
        return;
      }
      if (!is_array($subfields) && is_a($subfields, 'JsonOutput')) {
        $code = $subfields->getValue('@code');
        $value = $subfields->getValue();
        $this->_setData($tag, $code, $value, $index);
      }
      elseif(is_string($subfields)) {
        $value = $subfields;
        $code = $datafield->getValue('subfield/@code');
        $this->_setData($tag, $code, $value, $index);
      }
      elseif (is_array($subfields)) {
        foreach ($subfields as $subfield) {
          $code = $subfield->getValue('@code');
          $value = $subfield->getValue();
          $this->_setData($tag, $code, $value, $index);
        }
      }
      $index++;
    }
    unset($this->result);
  }

  public function getValue($field, $subfield = NULL, $index = -1) {
    if ($subfield) {
      if ($index == -1 && isset($this->data[$field][$subfield])) {
          return reset($this->data[$field][$subfield]);
      }
      elseif (isset($this->data[$field][$subfield][$index])) {
        return $this->data[$field][$subfield][$index];
      }
    }
    elseif (isset($this->data[$field])) {
      return $this->data[$field];
    }
    return NULL;
  }

  private function _setData($tag, $code, $value, $index) {
    if (!empty($this->data[$tag][$code][$index])) {
      if (is_array($this->data[$tag][$code][$index])) {
        $this->data[$tag][$code][$index][] = $value;
      }
      else {
        $tmp = $this->data[$tag][$code][$index];
        $this->data[$tag][$code][$index] = array($tmp);
        $this->data[$tag][$code][$index][] = $value;
      }
    }
    else {
      $this->data[$tag][$code][$index] = $value;
    }
  }
}
