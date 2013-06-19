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
        $this->_setData($tag, $code, $value);
      }
      elseif(is_string($subfields)) {
        $value = $subfields;
        $code = $datafield->getValue('@code');
        $this->_setData($tag, $code, $value);
      }
      else {
        if (is_array($subfields)) {
          foreach ($subfields as $subfield) {
            $code = $subfield->getValue('@code');
            $value = $subfield->getValue();
            $this->_setData($tag, $code, $value);
          }
        }
        else {
          var_dump($subfields);
          echo '<pre>'.print_r($data, 1).'<pre>';
        }
      }
    }
    unset($this->result);
  }

  public function getValue($field, $subfield = NULL) {
    if ($subfield != NULL) {
      if (isset($this->data[$field][$subfield])) {
        return $this->data[$field][$subfield];
      }
    }
    if (isset($this->data[$field])) {
      return $this->data[$field];
    }
    return NULL;
  }

  private function _setData($tag, $code, $value) {
    if (!empty($this->data[$tag][$code])) {
      if (is_array($this->data[$tag][$code])) {
        $this->data[$tag][$code][] = $value;
      }
      else {
        $tmp = $this->data[$tag][$code];
        $this->data[$tag][$code] = array($tmp);
        $this->data[$tag][$code][] = $value;
      }
    }
    else {
      $this->data[$tag][$code] = $value;
    }
  }
}
