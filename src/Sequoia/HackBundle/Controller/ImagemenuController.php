<?php

namespace Sequoia\HackBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\View\View;
use FOS\Rest\Util\Codes;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use TesseractOCR;
use Buzz\Browser;

class ImagemenuController extends Controller
{

	public function getImagemenuAction()
	{
			$nUrl      = 'https://api.nutritionix.com/v1_1/search/';
			$nAppId    = '07153193';
			$nAppKey   = '6219c8eaf48616c9ebaf0e031727e5fa';
			$buzz      = $this->get('buzz');
			$request   = $this->getRequest();
			$image     = $request->get('image');
			$tesseract = new TesseractOCR($image);
			$tesseract->setTempDir('/tmp');
			//$tesseract->setWhitelist(range('A','Z'), range('a','z'));
			$data = strtolower($tesseract->recognize());

			$blackListedKeys = array(
				'just',
				'starts',
				'starters',
				'vegetarian'
			);

			$breakKeys = array(
				']',
				'[',
				'/',
				'\\'
			);

			//str_replace($breakKeys, '' , $data);

			$words = preg_split('/[\s]+/', $data, -1, PREG_SPLIT_NO_EMPTY);

			$items = array();
			$combinedWord = null;

			$fields = 'nf_ingredient_statement,nf_calories,nf_total_fat,nf_serving_weight_grams';

			foreach ($words as $word) {
				if (!in_array($word, $blackListedKeys)) {
					if (intval($word)) {
						$combinedWord = trim($combinedWord);
						$id = time();
						$items[$id]['id'] = $id;
						$items[$id]['name'] = $combinedWord;

			            $combinedWord = urlencode($combinedWord);
						$ch = curl_init(); 
						//var_dump(new \DateTime());
					    curl_setopt($ch,CURLOPT_URL,"https://api.nutritionix.com/v1_1/search/$combinedWord?results=0:1&fields=item_name,nf_ingredient_statement,nf_calories,nf_total_fat,nf_serving_weight_grams&appId=07153193&appKey=6219c8eaf48616c9ebaf0e031727e5fa");
					    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
					 
					    $output = curl_exec($ch);

					    //var_dump(new \DateTime());die;

					    if ($output) {
				            $result = json_decode($output,true);
				            $fields = $result['hits'][0]['fields'];

							$items[$id]['item_name'] = $fields['item_name'];
							$items[$id]['ingredients'] = $fields['nf_ingredient_statement'];
							$items[$id]['calories'] = $fields['nf_calories'];
							$items[$id]['fat'] = $fields['nf_total_fat'];
							$items[$id]['serving_weight_grams'] = $fields['nf_serving_weight_grams'];
					    }
				        
						$combinedWord = null;
						continue;
					} else {
						$combinedWord .= ' '.$word;
					}
				}
			}

			return View::create(array_values($items));
	}
}