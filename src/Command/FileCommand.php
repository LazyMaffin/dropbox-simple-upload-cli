<?php

namespace App\Command;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'file', description: 'Dropbox file upload Command', hidden: false)]
class FileCommand extends Command
{
	protected function configure(): void
	{
		$this
			->addArgument('inputFile', InputArgument::REQUIRED, 'The path to the file')
			->addArgument('uploadPath', InputArgument::OPTIONAL, 'Upload path. Example: "/NewPath/', '/')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$io = new SymfonyStyle($input, $output);
		$io->title('Dropbox upload file.');

		$inputFile = trim($input->getArgument('inputFile'));
		$uploadPath = trim($input->getArgument('uploadPath'));
		$inputFilePath = realpath($inputFile);
		if (!$inputFilePath || !file_exists($inputFilePath)) {
			throw new RuntimeException(
				'The input file does not exist! Please check the path and permissions for the file.'
			);
		}

		$configFile = PROJECT_ROOT_DIR.'config.json';
		if (!file_exists($configFile)) {
			throw new RuntimeException(
				'The configuration file is not found! To initiate, please use "php upload.php init".'
			);
		}
		$configJson = file_get_contents($configFile);
		if ($configJson === false) {
			throw new RuntimeException(
				'Unable to read the configuration file. Please check the read permissions.'
			);
		}
		$configArray = json_decode($configJson, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new RuntimeException(
				'The configuration file is corrupted. Unable to fetch configuration. Reason: '.json_last_error_msg()
			);
		}

		$client = new Client(['timeout' => 5]);
		$response = $client->post('https://api.dropboxapi.com/2/check/user', [
			'json' => ['query' => 'foo'],
			'headers' => [
				'Authorization' => sprintf('Bearer %s', $configArray['access_token']),
				'Content-Type' => 'application/json',
			],
			'http_errors' => false,
		]);

		if ($response->getStatusCode() === 200) {
			$io->note('Token is valid.');
		} else {
			$io->note('Refreshing token.');

			$response = $client->post('https://api.dropbox.com/oauth2/token', [
				'form_params' => [
					'refresh_token' => $configArray['refresh_token'],
					'grant_type' => 'refresh_token',
					'client_id' => $configArray['app_key'],
					'client_secret' => $configArray['app_secret'],
				],
			]);

			$responseJson = $response->getBody()->getContents();
			$responseArray = json_decode($responseJson, true);

			$configArray['access_token'] = $responseArray['access_token'];
			$configArray['expires_in'] = $responseArray['expires_in'];

			if (!file_put_contents($configFile, json_encode($configArray))) {
				throw new RuntimeException(
					'Unable to save configuration to the file. Please check the write permissions for the working folder.'
				);
			}
		}

		$body = Utils::tryFopen($inputFilePath, 'r');
		$parameters = [
			"autorename" => false,
			"mode" => "add",
			"mute" => false,
			"path" => sprintf('%s%s', $uploadPath, basename($inputFile)),
			"strict_conflict" => false,
		];
		$client->request('POST', 'https://content.dropboxapi.com/2/files/upload', [
			'body' => $body,
			'headers' => [
				'Authorization' => sprintf('Bearer %s', $configArray['access_token']),
				'Dropbox-API-Arg' => json_encode($parameters),
				'Content-Type' => 'application/octet-stream',
			],
		]);

		$io->success('Done!');

		return Command::SUCCESS;
	}
}