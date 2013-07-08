<?php
/*
 * Kimios - Document Management System Software
 * Copyright (C) 2012-2013  DevLib'
 * Copyright (C) 2013 - François Legastelois (flegastelois@teclib.com)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

//require init api file
require('init.api.php');

//specific config for this example
$folderId = 3;

/*****
* Initialize SESSION
*****/
$KimiosPhpSoap = new KimiosPhpSoap();
$KimiosPhpSoap->connect($config['url'],$config['userSource'],
							$config['userName'],$config['password']);
$sessionId = $KimiosPhpSoap->getSessionId(); 

/*****
* retrieve all documents in the folderId
*****/
$DocumentService = new DocumentService(
	$KimiosPhpSoap->getWsdl($config['url'], 'DocumentService'), 
	$KimiosPhpSoap->getArrayOptionsService($config['url'], 'DocumentService'));

$documents = new getDocuments(
	array('sessionId' => $sessionId,
		'folderId' => $folderId)
);

$documentsResp = $DocumentService->getDocuments($documents);
$allDocuments = $documentsResp->return->Document;

//show all documents
foreach($allDocuments as $documentObject) {
	
	echo "<p style='font-weight:bold;'>".$documentObject->name."</p>";

	echo "<ul>";

		foreach(array('uid', 'mimeType', 'extension', 'owner',
					'creationDate', 'documentTypeName', 'length', 'path') as $docProperty) {
			echo "<li><b>".$docProperty." : </b>".$documentObject->$docProperty."</li>";
		}

	echo "</ul>";
}