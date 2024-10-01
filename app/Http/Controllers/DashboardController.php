<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function dashboard()
    {
        return view('dashboard');
    }

    public function discogs()
    {
        $requestToken = $this->getRequestToken();

        $authorizationUrl = "https://www.discogs.com/oauth/authorize?oauth_token={$requestToken}";
        return response()->json(['authorizationUrl' => $authorizationUrl]);
    }

    public function getRequestToken()
    {
        $consumerKey = 'jMTlTzfLUTJXNMifbEjw'; // Remplacez par votre Consumer Key
        $consumerSecret = 'AFZKNRuZJOwAQfgfbuVKInxKjSUXNPGg'; // Remplacez par votre Consumer Secret
        $url = 'https://api.discogs.com/oauth/request_token';

        // Préparation des paramètres OAuth
        $oauthTimestamp = time();
        $oauthNonce = bin2hex(random_bytes(16)); // Générer un nonce unique
        $oauthSignatureMethod = 'HMAC-SHA1';
        $oauthVersion = '1.0';

        // Créer la base de la signature
        $baseParams = [
            'oauth_consumer_key' => $consumerKey,
            'oauth_nonce' => $oauthNonce,
            'oauth_signature_method' => $oauthSignatureMethod,
            'oauth_timestamp' => $oauthTimestamp,
            'oauth_version' => $oauthVersion,
        ];

        // Construire le base string pour la signature
        $baseString = "POST&" . rawurlencode($url) . '&' . rawurlencode(http_build_query($baseParams, '', '&'));

        // Générer la signature
        $key = rawurlencode($consumerSecret) . '&';
        $oauthSignature = base64_encode(hash_hmac('sha1', $baseString, $key, true));
        $baseParams['oauth_signature'] = $oauthSignature;

        // Préparer les en-têtes de la requête
        $authHeader = 'Authorization: OAuth ' . http_build_query($baseParams, '', ', ');

        // Faire la requête pour obtenir le request token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [$authHeader]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'cwlBlaster/1.0 (Contact: advitameternam@gmail.com)');
        curl_setopt($ch, CURLOPT_POSTFIELDS, ''); // Aucun corps de requête nécessaire

        $response = curl_exec($ch);
        curl_close($ch);

        // Traiter la réponse
        parse_str($response, $result); // Convertir la réponse en tableau associatif

        if (isset($result['oauth_token']) && isset($result['oauth_token_secret'])) {
            return $result['oauth_token']; // Retourner le request token
        } else {
            throw new Exception('Erreur lors de l\'obtention du request token : ' . $response);
        }
    }

    public function callback(Request $request)
    {
        $oauthToken = $request->input('oauth_token');
        $oauthVerifier = $request->input('oauth_verifier');

        if (!$oauthToken || !$oauthVerifier) {
            return response()->json(['error' => 'Missing oauth_token or oauth_verifier']);
        }

        // Obtenir l'access token en utilisant $oauthToken et $oauthVerifier
        $this->getAccessToken($oauthToken, $oauthVerifier);
    }

    public function getAccessToken($oauthToken, $oauthVerifier)
    {
        $consumerKey = 'jMTlTzfLUTJXNMifbEjw';
        $consumerSecret = 'AFZKNRuZJOwAQfgfbuVKInxKjSUXNPGg';
        $url = 'https://api.discogs.com/oauth/access_token';

        // Préparer les paramètres OAuth
        $oauthParams = [
            'oauth_consumer_key' => $consumerKey,
            'oauth_token' => $oauthToken,
            'oauth_nonce' => uniqid(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_verifier' => $oauthVerifier,
            'oauth_version' => '1.0',
        ];

        // Créer la base de la signature
        $baseString = "POST&" . rawurlencode($url) . '&' . rawurlencode(http_build_query($oauthParams, '', '&'));

        // Générer la signature
        $key = rawurlencode($consumerSecret) . '&';
        $oauthParams['oauth_signature'] = rawurlencode(base64_encode(hash_hmac('sha1', $baseString, $key, true)));

        // Préparer l'en-tête de la requête
        $authHeader = 'Authorization: OAuth ' . http_build_query($oauthParams, '', ', ');

        // Faire la requête
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [$authHeader, 'User-Agent: VotreUserAgent']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'cwlBlaster/1.0 (Contact: advitameternam@gmail.com)');
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Erreur cURL: ' . curl_error($ch);
        }

        curl_close($ch);

        // Traiter la réponse
        parse_str($response, $output);
        if (isset($output['oauth_token']) && isset($output['oauth_token_secret'])) {
            $accessToken = $output['oauth_token'];
            $accessTokenSecret = $output['oauth_token_secret'];

            // Vous pouvez maintenant enregistrer ces tokens pour les utiliser dans les requêtes API
            return response()->json(['access_token' => $accessToken, 'access_token_secret' => $accessTokenSecret]);
        } else {
            return response()->json(['error' => 'Failed to obtain access token']);
        }
    }
}
