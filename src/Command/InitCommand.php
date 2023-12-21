<?php

namespace App\Command;

use GuzzleHttp\Client;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'init', description: 'Dropbox token initialization Command', hidden: false)]
class InitCommand extends Command
{
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$io = new SymfonyStyle($input, $output);
		$io->title('Dropbox upload initialization script.');

		$configFile = PROJECT_ROOT_DIR.'config.json';
		$io->note(sprintf('Configuration file will be saved in: %s', $configFile));
		if (
			file_exists($configFile)
			&& ($configJson = file_get_contents($configFile)) !== false
		) {
			$configArray = json_decode($configJson, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				$configArray = [];
			}
		} else {
			$configArray = [];
		}

		$defaultAnswerNormalizer = fn(?string $value) => $value ? trim($value) : '';
		$appKeyValidator = function (?string $answer, ?string $answerDefault): string {
			if (!$answerDefault && (!is_string($answer) || strlen($answer) < 15)) {
				throw new RuntimeException(
					'The provided value is not valid. The value should be more than 10 characters.'
				);
			}

			return $answer;
		};

		$answerDefault = $configArray['app_key'] ?? null;
		$configArray['app_key'] = $io->askQuestion(
			(new Question('Please input APP_KEY', $answerDefault))
				->setNormalizer($defaultAnswerNormalizer)
				->setValidator(fn($answer) => $appKeyValidator($answer, $answerDefault))
				->setMaxAttempts(2));

		$answerDefault = $configArray['app_secret'] ?? null;
		$configArray['app_secret'] = $io->askQuestion(
			(new Question('Please input APP_SECRET', $answerDefault))
				->setNormalizer($defaultAnswerNormalizer)
				->setValidator(fn($answer) => $appKeyValidator($answer, $answerDefault))
				->setMaxAttempts(2));

		$authUrl = sprintf('https://www.dropbox.com/oauth2/authorize?client_id=%s&response_type=code&token_access_type=offline',
			$configArray['app_key']
		);
		$io->text(sprintf('1. Go to: <href=%s>%s</>', $authUrl, $authUrl));
		$io->text('2. Click "Allow" (you might have to log in first).');
		$io->text('3. Copy the authorization code.');
		$io->writeln("");

		$authCode = $io->askQuestion(
			(new Question('Enter the authorization code here'))
				->setNormalizer($defaultAnswerNormalizer)
				->setValidator(function (?string $answer): string {
					if (!is_string($answer) || strlen($answer) < 9) {
						throw new RuntimeException(
							'The provided code is not valid. The code should be more than 10 characters.'
						);
					}

					return $answer;
				})->setMaxAttempts(2));

		$client = new Client(['timeout' => 5]);
		$response = $client->post('https://api.dropboxapi.com/oauth2/token', [
			'form_params' => [
				'code' => $authCode,
				'grant_type' => 'authorization_code',
				'client_id' => $configArray['app_key'],
				'client_secret' => $configArray['app_secret'],
			],
		]);

		$responseJson = $response->getBody()->getContents();
		$responseArray = json_decode($responseJson, true);

		$configArray = array_merge($configArray, $responseArray);

		if (!file_put_contents($configFile, json_encode($configArray))) {
			throw new RuntimeException(
				'Unable to save configuration to the file. Please check the write permissions for the working folder.'
			);
		}

		$response = $client->post('https://api.dropboxapi.com/2/users/get_current_account', [
			'headers' => [
				'Authorization' => sprintf('Bearer %s', $configArray['access_token']),
			]
		]);

		if ($responseArray = json_decode($response->getBody()->getContents(), true)) {
			$io->success(sprintf(
				"Welcome, %s!\nIt's done! You can now proceed to upload files to your Dropbox storage.",
				$responseArray['name']['display_name'] ?? 'New User'
			));

			return Command::SUCCESS;
		} else {
			$io->error("Something went wrong... 😞\nPlease try again.");

			return Command::FAILURE;
		}
	}
}