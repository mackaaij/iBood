<?php
/**
 * Created by PhpStorm.
 * User: Arthur
 * Date: 21-03-17
 * Time: 11:26
 */

include_once "pushbullet.class.php";
include_once "web.class.php";

$pushbullet = new Pushbullet();
$web = new web();

$pushbullet->setToken("");
$url = "http://feeds.ibood.com/nl/nl/offer.jsonp";

$recheckTime = 3;

$seenItems = array();

$oldId = 0;

while (true) {
	$output = $web->get($url);

	if (strlen($output) > 20) {
		$continue = true;
		$cleaned = substr($output, 6, strlen($output) - 8);

		$decoded = json_decode($cleaned);

		$id = $decoded->Id;
		$title = $decoded->Title;
		$permaLink = $decoded->Permalink;
		$prices = $decoded->Price;
		$delivery = $decoded->Delivery;
		$priceDeliv = $prices . $delivery;

		$newPrice = "";
		$oldPrice = "";
		$korting = "";

		$deliveryDate = "";

		if (array_key_exists($id, $seenItems)) {
			if ((time() - $seenItems[$id]["firstSeen"]) < 600) {
				$continue = false;
			}
		}

		/*if ($oldId == $id) {
			$continue = false;
		}*/


		if ($continue) {
			$regexArray = array(
				"newPrice" => "/<span class=\"new-price\">(?P<variabele>(.*?|\s)+)\<\/span\>/",
				"oldPrice" => "/<span class=\"strike\">\<span\>(?P<variabele>(.*?|\s)+)\<\/span\>/",
				"korting" => "/<span class=\"discount\">(?P<variabele>(.*?|\s)+)\<\/span\>/",
				"deliveryDate" => "/Verwachte verzenddatum: (?P<variabele>(.*?|\s)+)\<\/span\>/",
			);

			foreach ($regexArray as $key => $regex) {
				if (preg_match_all($regex, $priceDeliv, $matches)) {
					$$key = $matches['variabele'][0];
				}
			}

			$message = $title . "\r\n" . "Price now: $newPrice" . "\r\n" . "Old Price: $oldPrice" . "\r\n" . "Korting: $korting" . "\r\n" . $permaLink;
			$message = urlencode($message);

			if (preg_match("/doos/", $title)) {
				for ($i = 0; $i < 5; $i++) {
					$pushbullet->sendPush("iBOOD BOX", "IBOOD BOX MOFO, GO GET IT", "note");
				}
			}

			//if (date("H", time()) < 2 || date("H", time()) > 10) {
				$pushbullet->sendPush("iBOOD", $message, "note");
			//}

			$oldId = $id;

			$seenItems[$id] = array("title" => $title, "firstSeen" => time());
		}
	} else {
		//$pushbullet->sendPush("Error - iBOOD", "Script didn't get a valid response from the website.", "note");
	}

	sleep($recheckTime);
}
?>
