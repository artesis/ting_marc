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
   * Extract marcxchange data.
   *
   * @param array $data
   *   Result received after request.
   *
   * @return array|void|null
   */
  public function extractCollection($data) {
    $collectionData = reset($data);

    // TODO: This is workaround when item is not accessible anymore.
    if (!$collectionData instanceof JsonOutput && array_key_exists('error', $collectionData)) {
      unset($this->result);
      return;
    }

    // Try to retrieve bibliographic item data.
    $data = $collectionData->getValue('collection/record/datafield');

    // If `$data` is empty, when assume that collection contains more types of
    // records, so we will extract the main.
    if (empty($data)) {
      $data_array = $collectionData->getValue('collection/record');

      // For some reasons, sometimes item misses 'collection/record' value, then we just skip processing of this item.
      if (empty($data_array)) {
        return;
      }

      if ($data_array instanceof JsonOutput) {
        $data = $data_array->getValue('datafield');
      }
      else {
        $data = $data_array[0]->getValue('datafield');
      }
    }

    return $data;
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
    $data = $this->extractCollection($data);

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
        $data = $this->data[$field][$subfield];
        if (is_array($data)) {
          if (is_array(reset($data))) {
            return array_map(array($this, 'mergeArray'), $data);
          }
          return $this->mergeArray($data);
        }
        return $data;
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

  protected function mergeArray($item){
    return implode(', ', $item);
  }
}
