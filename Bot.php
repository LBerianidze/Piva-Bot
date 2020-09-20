<?php
/**
 * Created by PhpStorm.
 * User: Лука
 * Date: 14.04.2020
 * Time: 12:32
 */

use Longman\TelegramBot\Request;

include('vendor/autoload.php');
include "BotHelper.php";
include "dbconfig.php";
include "keyboards.php";
$milliseconds = round(microtime(true) * 1000);
file_put_contents("messages/$milliseconds.txt", file_get_contents("php://input"));

$telegram = new Longman\TelegramBot\Telegram('1184178751:AAH6aTqvQksDntzM4rftulqe_NqEYFDlbGM', '@YouCanB_bot');
$db_config = new DBConfig();
$telegram->useGetUpdatesWithoutDatabase();
$telegram->handle();
$updates = $telegram->handleGetUpdates();
$message_str = $updates->getProperty('message');
$message = new \Longman\TelegramBot\Entities\Message($message_str);

if ($message_str != null)
{
	$type = $message->getType();
	$chat_id = getChatId($message);
	if ($type == 'text')
	{
		$text = $message->getText();
		if ($text == '🍺 Заказать')
		{
			$beers = $db_config->getBeerList();
			foreach ($beers as $beer)
			{
				$count = $db_config->checkIfBeerExistsInBasket($chat_id, $beer['id']);
				$result = Request::sendPhoto([
					'chat_id'      => $chat_id,
					'photo'        => 'https://vh368883.eurodir.ru/images/' . $beer['image'],
					'caption'      => $beer['brewery'] . ", " . $beer['name'] . '(' . str_replace('/', '\\', $beer['percent']) . ')',
					'reply_markup' => getBeerKeyboard($beer, $count)
				]);
			}
		}
		else if ($text == '🛍 Корзина')
		{
			showBasket();
		}
		else if ($text == 'О Нас')
		{
			$result = Request::sendMessage([
				'chat_id'    => $chat_id,
				'text'       => file_get_contents("about.txt"),
				'parse_mode' => "HTML"
			]);
		}
		else
		{
			$user = $db_config->getUser($chat_id);
			switch ($user['order_step'])
			{
				case 1:
					{
						$db_config->setName($chat_id, $text);
						$db_config->setStep($chat_id, 2);
						Request::sendMessage([
							'text'    => 'Пожалуйста,введите Ваш номер телефона: ',
							'chat_id' => $chat_id
						]);
						break;
					}
				case 2:
					{
						$db_config->setPhone($chat_id, $text);
						$db_config->setStep($chat_id, 3);
						Request::sendMessage([
							'text'    => 'Пожалуйста,введите ваш адрес: ',
							'chat_id' => $chat_id
						]);
						break;
					}
				case 3:
					{
						$user['address'] = $text;
						$db_config->setAddress($chat_id, $text);
						$db_config->setStep($chat_id, 4);
						Request::sendMessage([
							'text'         => generateMessage($user),
							'chat_id'      => $chat_id,
							'parse_mode'   => 'Markdown',
							'reply_markup' => getConfirmKeyboard()
						]);
						break;
					}
			}
		}
	}
	else if ($type == 'command')
	{
		$command = $message->getCommand();
		if ($command == 'start')
		{
			if (!$db_config->userExists($chat_id))
			{
				$db_config->addUser($chat_id);
			}
			$result = Request::sendMessage([
				'chat_id'      => $chat_id,
				'text'         => file_get_contents('start.txt'),
				'reply_markup' => getMainKeyboard()
			]);
		}
	}
}
else
{
	$callback_str = $updates->getProperty('callback_query');
	if($callback_str == null)
		exit();
	$callback = new \Longman\TelegramBot\Entities\CallbackQuery($callback_str);
	$chat_id = $callback->getFrom()->getId();
	$data = $callback->getData();
	if (substr($data, 0, 7) == 'AddBeer')
	{
		$beer_id = explode('_', $data)[1];
		$beer = $db_config->getBeer($beer_id);
		$count = $db_config->addBeerToBasket($chat_id, $beer_id, $beer['price']);
		$result = Request::editMessageCaption([
			'chat_id'      => $callback->getFrom()->getId(),
			'message_id'   => $callback->getMessage()->getMessageId(),
			'caption'      => $beer['brewery'] . "\n" . $beer['name'] . '(' . str_replace('/', '\\', $beer['percent']) . ')',
			'reply_markup' => getBeerKeyboard($beer, $count)
		]);
	}
	elseif (substr($data, 0, 13) == 'IncrementBeer' || substr($data, 0, 13) == 'DecrementBeer')
	{
		$beer_id = explode('_', $data)[1];
		if (substr($data, 0, 13) == 'IncrementBeer')
		{
			$count = $db_config->addBeerToBasket($chat_id, $beer_id);
		}
		else
		{
			$count = $db_config->removeBeerFromBasket($chat_id, $beer_id);
		}
		$beers = $db_config->getAllBasketBeers($chat_id);
		$beer = $db_config->getBeer($beer_id);
		for ($i = 0; $i < count($beers); $i++)
		{
			if ($beers[$i]['beer_id'] == $beer_id)
			{
				$index = $i + 1;
				break;
			}
		}
		$total_price = 0;
		$total_count = 0;
		for ($i = 0; $i < count($beers); $i++)
		{
			$total_price += $beers[$i]['count'] * $beers[$i]['price'];
			$total_count += $beers[$i]['count'];
		}
		$beer_name = $beer['name'];
		$result = Request::editMessageText([
			'chat_id'      => $chat_id,
			'message_id'   => $callback->getMessage()->getMessageId(),
			'text'         => "Корзина:\n🔹<a href=\"https://vh368883.eurodir.ru/images/" . $beer['image'] . "\">$beer_name</a>" . "\n" . $beer['brewery'] . '(' . str_replace('/', '\\', $beer['percent']) . ")\n\n" . $beer['price'] . ' ₽ * ' . $count . ' шт. = ' . ($count * $beer['price']) . ' ₽',
			'parse_mode'   => "HTML",
			'reply_markup' => getOrderKeyboard($beer, $count, count($beers), $index, $total_price, $total_count)
		]);
	}
	else if (substr($data, 0, 10) == 'RemoveBeer')
	{
		$beer_id = explode('_', $data)[1];
		$db_config->deleteBeerFromBasket($chat_id, $beer_id);
		showBasket($callback->getMessage()->getMessageId());
	}
	else if (substr($data, 0, 8) == 'NextBeer')
	{
		$beer_id = explode('_', $data)[1];
		$beers = $db_config->getAllBasketBeers($chat_id);
		$beer = $db_config->getBeer($beer_id);
		for ($i = 0; $i < count($beers); $i++)
		{
			if ($beers[$i]['beer_id'] == $beer_id)
			{
				$index = $i + 1;
				break;
			}
		}
		if ($index < count($beers))
		{
			showBasket($callback->getMessage()->getMessageId(), $index);
		}
	}
	else if (substr($data, 0, 12) == 'PreviousBeer')
	{
		$beer_id = explode('_', $data)[1];
		$beers = $db_config->getAllBasketBeers($chat_id);
		$beer = $db_config->getBeer($beer_id);
		for ($i = 0; $i < count($beers); $i++)
		{
			if ($beers[$i]['beer_id'] == $beer_id)
			{
				$index = $i;
				break;
			}
		}
		if ($index > 0)
		{
			showBasket($callback->getMessage()->getMessageId(), $index - 1);
		}
	}
	else if ($data == 'ShowBasket')
	{
		showBasket();
	}
	else if ($data === 'CreateOrder')
	{
		$beers = $db_config->getAllBasketBeers($chat_id);
		$count = 0;
		for ($i = 0; $i < count($beers); $i++)
		{
			$count += $beers[$i]['count'];
		}
		if ($count % 20 != 0)
		{
			Request::sendMessage([
				'text'    => '❌ Количество банок для заказа должно быть кратно 20',
				'chat_id' => $chat_id
			]);
		}
		else
		{
			$db_config->setStep($chat_id, 1);
			Request::sendMessage([
				'text'    => 'Пожалуйста,введите ваше имя: ',
				'chat_id' => $chat_id
			]);
		}
	}
	else if ($data == 'ConfirmOrder')
	{
		$user = $db_config->getUser($chat_id);
		$beers = $db_config->getAllBasketBeers($chat_id);
		$beer_json_array = array();
		foreach ($beers as $item)
		{
			$beer = array();
			$beer[] = $item['beer_id'];
			$beer[] = $item['count'];
			$beer_json_array[] = $beer;
		}
		$db_config->createOrder($chat_id, $user['name'], $user['address'], $user['phone'], json_encode($beer_json_array));
		$db_config->clearBasket($chat_id);
		Request::deleteMessage([
			'message_id' => $callback->getMessage()->getMessageId(),
			'chat_id'    => $chat_id
		]);
		Request::sendMessage([
			'text'    => "Благодарим Вас за заказ!\nС Вами скоро свяжутся!",
			'chat_id' => $chat_id
		]);
	}
}
function generateMessage($user)
{
	global $db_config;
	$message = "*Данные заказа*\n";
	$beers = $db_config->getAllBasketBeers($user['telegram_id']);
	$total_price = 0;
	$total_count = 0;
	for ($i = 0; $i < count($beers); $i++)
	{
		$total_price += $beers[$i]['count'] * $beers[$i]['price'];
		$total_count += $beers[$i]['count'];
	}
	$message .= "Количество банок пива: *$total_count шт.*\n";
	$message .= "Общая сумма заказа: *$total_price ₽.*\n";
	$message .= 'Имя: ' . $user['name'] . "\n";
	$message .= 'Телефон: ' . $user['phone'] . "\n";
	$message .= 'Адрес: ' . $user['address'] . "\n";
	$message .= 'Если все верно,нажмите кнопку "Подтвердить" 👇';

	return $message;
}

function showBasket($message_id = false, $ind = false)
{
	global $db_config;
	global $chat_id;
	$beers = $db_config->getAllBasketBeers($chat_id);
	if (count($beers) == 0)
	{
		Request::sendMessage([
			'chat_id' => $chat_id,
			'text'    => 'В корзине пусто!'
		]);
		return;
	}
	$beer = $db_config->getBeer($beers[$ind]['beer_id']);
	$count = $db_config->checkIfBeerExistsInBasket($chat_id, $beer['id']);
	for ($i = 0; $i < count($beers); $i++)
	{
		if ($beers[$i]['beer_id'] == $beers[$ind]['beer_id'])
		{
			$index = $i + 1;
			break;
		}
	}
	$total_price = 0;
	$total_count = 0;
	for ($i = 0; $i < count($beers); $i++)
	{
		$total_price += $beers[$i]['count'] * $beers[$i]['price'];
		$total_count += $beers[$i]['count'];
	}

	$beer_name = $beer['name'];
	if (!$message_id)
	{
		Request::sendMessage([
			'chat_id'      => $chat_id,
			'text'         => "Корзина:\n🔹<a href=\"https://vh368883.eurodir.ru/images/" . $beer['image'] . "\">$beer_name</a>" . "\n" . $beer['brewery'] . '(' . str_replace('/', '\\', $beer['percent']) . ")\n\n" . $beer['price'] . ' ₽ * ' . $count . ' шт. = ' . ($count * $beer['price']) . ' ₽',
			'parse_mode'   => "HTML",
			'reply_markup' => getOrderKeyboard($beer, $count, count($beers), $index, $total_price, $total_count)
		]);
	}
	else
	{
		Request::editMessageText([
			'chat_id'      => $chat_id,
			'message_id'   => $message_id,
			'text'         => "Корзина:\n🔹<a href=\"https://vh368883.eurodir.ru/images/" . $beer['image'] . "\">$beer_name</a>" . "\n" . $beer['brewery'] . '(' . str_replace('/', '\\', $beer['percent']) . ")\n\n" . $beer['price'] . ' ₽ * ' . $count . ' шт. = ' . ($count * $beer['price']) . ' ₽',
			'parse_mode'   => "HTML",
			'reply_markup' => getOrderKeyboard($beer, $count, count($beers), $index, $total_price, $total_count)
		]);
	}
}

function writeDump($item)
{
	ob_flush();
	ob_start();
	var_dump($item);
	file_put_contents("dump.txt", ob_get_flush());
}