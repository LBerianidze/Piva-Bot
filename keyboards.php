<?php
/**
 * Created by PhpStorm.
 * User: Лука
 * Date: 15.04.2020
 * Time: 13:00
 */
function getOrderKeyboard($beer, $count, $beer_count, $index, $total_price,$total_count)
{
	$ikb = new Longman\TelegramBot\Entities\InlineKeyboard([
		[
			'callback_data' => 'RemoveBeer_' . $beer['id'],
			'text'          => '❌'
		],
		[
			'callback_data' => 'IncrementBeer_' . $beer['id'],
			'text'          => '🔺'
		],
		[
			'callback_data' => 'Empty',
			'text'          => $count . ' шт.'
		],
		[
			'callback_data' => 'DecrementBeer_' . $beer['id'],
			'text'          => '🔻'
		]
	]);
	$ikb->addRow([
		'callback_data' => 'PreviousBeer_' . $beer['id'],
		'text'          => '⬅️'
	], [
		'callback_data' => 'count',
		'text'          => $index . ' / ' . $beer_count
	], [
		'callback_data' => 'NextBeer_' . $beer['id'],
		'text'          => '➡️'
	]);
	$ikb->addRow([
		'callback_data' => 'CreateOrder',
		'text'          => '✅ Заказ на ' . $total_price . ' ₽ за '.$total_count.' шт. Оформить?'
	]);
	return $ikb;
}

function getBeerKeyboard($beer, $count)
{
	$ikb = new Longman\TelegramBot\Entities\InlineKeyboard([
		[
			'text'          => 'Добавить в корзину — ' . $beer['price'] . ' ₽ ' . ($count == 0 ? '' : '(' . $count . ')'),
			'callback_data' => 'AddBeer_' . $beer['id']
		]
	]);
	if ($count != 0)
	{
		$ikb->addRow([
			'text'          => 'Перейти в корзину',
			'callback_data' => 'ShowBasket'
		]);
	}
	return $ikb;
}
function getConfirmKeyboard()
{
	$ikb = new Longman\TelegramBot\Entities\InlineKeyboard([
		[
			'text'          => 'Подтвердить заказ✅',
			'callback_data' => 'ConfirmOrder'
		]
	]);
	return $ikb;
}

function getMainKeyboard()
{
	$keyboards[] = new Longman\TelegramBot\Entities\Keyboard([
		'🍺 Заказать',
		'🛍 Корзина'
	], [
		'О Нас'
	]);
	$keyboard = $keyboards[0];
	$keyboard->setResizeKeyboard(true);
	$keyboard->setOneTimeKeyboard(false);
	$keyboard->setSelective(false);
	return $keyboard;
}