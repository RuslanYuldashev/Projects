<?
include_once("connect.php");

abstract class abstract_parser
{
	protected $book;
	
    abstract public function parse($filename);
	
	private function check_ean()
	{
		$control_sum = 0;
		for($i = 0; $i < strlen($this->book['ean']) - 1; $i++)
		{
			if ($i%2)
			{
				$control_sum += $this->book['ean'][$i]*3;
			}
			else
			{
				$control_sum += $this->book['ean'][$i];
			}
		}
		$control_sum = (10 - ($control_sum % 10)) % 10;
		$this->book['ean'][strlen($this->book['ean']) - 1] = $control_sum;
		$this->book['isbn'][strlen($this->book['isbn']) - 1] = $control_sum;
	}
	
    public function write_info(){
		global $conn;
		$this->check_ean();
		//проверка language
		$sql = "SELECT * FROM language WHERE name IN ('".$this->book['language']."')";
		$stmt = sqlsrv_query($conn, $sql);
		$row = sqlsrv_fetch_array($stmt);
		if ($row) {
			$this->book['language'] = $row['id'];
		}			
		else {
			$sql = "INSERT INTO language (name) VALUES ('".$this->book['language']."')";
			$stmt = sqlsrv_query($conn, $sql);
			$sql = "SELECT * FROM language WHERE name IN ('".$this->book['language']."')";
			$stmt = sqlsrv_query($conn, $sql);
			$row = sqlsrv_fetch_array($stmt);
			$this->book['language'] = $row['id'];
		}
		
		//проверка series
		$sql = "SELECT * FROM series WHERE name IN ('".$this->book['series']."')";
		$stmt = sqlsrv_query($conn, $sql);
		$row = sqlsrv_fetch_array($stmt);
		if ($row) {
			$this->book['series'] = $row['id'];
		}			
		else {
			$sql = "INSERT INTO series (name) VALUES ('".$this->book['series']."')";
			$stmt = sqlsrv_query($conn, $sql);
			$sql = "SELECT * FROM series WHERE name IN ('".$this->book['series']."')";
			$stmt = sqlsrv_query($conn, $sql);
			$row = sqlsrv_fetch_array($stmt);
			$this->book['series'] = $row['id'];
		}
		
		$sql = "INSERT INTO books (isbn, ean, name, description, netto, brutto, language, series, code) VALUES ('".$this->book["isbn"]."', '".$this->book["ean"]."', '".$this->book["name"]."', '".$this->book["description"]."', ".$this->book["netto"].", ".$this->book["brutto"].", ".$this->book["language"].", ".$this->book["series"].", '".$this->book["code"]."')";
		$stmt = sqlsrv_query($conn, $sql);
	}

}

class xml_parser extends abstract_parser
{
	private $reader;
	private $tag;
	
	private function get_info($parse_book){
	
		$this->book['code'] = $parse_book["attr"]["id"];
		$this->book['netto'] = $parse_book["price"];
		$this->book['brutto'] = round($this->book['netto'] + 0.1*$this->book['netto']);
		$this->book['name'] = $parse_book["name"];
		$this->book['language'] = $parse_book["language"];
		$this->book['description'] = $parse_book["description"];
		$this->book['isbn'] = preg_replace('/[^0-9-]/', '', $parse_book["isbn"]);
		$this->book['ean'] = preg_replace('/-/','',$this->book['isbn']);
		$this->book['series'] = $parse_book["param"];

	}
	
	private function parse_block($name, $ignoreDepth = 1) {
		if ($this->reader->name == $name && $this->reader->nodeType == XMLReader::ELEMENT) {
			$result = array();
			while (!($this->reader->name == $name && $this->reader->nodeType == XMLReader::END_ELEMENT)) { 
				switch ($this->reader->nodeType) {
					case 1:
						if ($this->reader->depth > 3 && !$ignoreDepth) { 
							$result[$nodeName] = (isset($result[$nodeName]) ? $result[$nodeName] : array());
							while (!($this->reader->name == $nodeName && $this->reader->nodeType == XMLReader::END_ELEMENT)) {
								$resultSubBlock = $this->parseBlock($this->reader->name, 1);
								
								if (!empty($resultSubBlock))
									$result[$nodeName][] = $resultSubBlock;
								
								unset($resultSubBlock);
								$this->reader->read();
							}
						}
						$nodeName = $this->reader->name;
						if ($this->reader->hasAttributes) {
							$attributeCount = $this->reader->attributeCount;
							
							for ($i = 0; $i < $attributeCount; $i++) {
								$this->reader->moveToAttributeNo($i);
								$result['attr'][$this->reader->name] = $this->reader->value;
							}
							$this->reader->moveToElement();
						}
						break;
					
					case 3:
					case 4: 
						$result[$nodeName] = $this->reader->value;
						$this->reader->read();
						break;
				}
				
				$this->reader->read();
			}
			return $result;
		}
	}
	
    public function parse($filename) {
		
		if (!$filename) return array();
		
		$this->reader = new XMLReader();
		$this->reader->open($filename);
		
		while ($this->reader->read()) {
			if ($this->reader->name == 'books') {
			while (!($this->reader->name == 'books' && $this->reader->nodeType == XMLReader::END_ELEMENT  )) {
					$book = $this->parse_block('book');
					if (!is_null($book)) {
						$this->get_info($book);
						$this->write_info();
					}
					$this->reader->read();
			}
			$this->reader->read();
		}
			
		} 
	} 


}

class txt_parser extends abstract_parser //создавая классы XXX_parser, можно адаптировать код под любой тип файла
{
    public function parse($filename) {
        //code
    }

}
?>