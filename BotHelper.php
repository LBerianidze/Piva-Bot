<?php
/**
 * Created by PhpStorm.
 * User: Лука
 * Date: 14.04.2020
 * Time: 14:58
 */
/**
 * @param \Longman\TelegramBot\Entities\Message $message
 * @return mixed
 */
function getChatId($message)
{
	$update_type = $message->getType();
	if ($update_type == 'text' || $update_type=='command')
	{
		return $message->getChat()->getId();
	}
	else
	{
		return $message->getFrom()->getId();
	}
}