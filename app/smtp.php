<?php

namespace App;

class Smtp extends Controller {

	function get($f3) {
		$test=new \Test;
		$test->expect(
			is_null($f3->get('ERROR')),
			'No errors expected at this point'
		);
		// CRLF injection in MAIL FROM is prevented
		$smtp=new \SMTP('localhost',25);
		$smtp->set('From',"legit@example.com\r\nBcc:attacker@evil.com");
		$smtp->set('To','victim@example.com');
		$smtp->set('Subject','Test');
		$smtp->send('Hello',TRUE,TRUE);
		$test->expect(
			preg_match('/\nBcc:attacker/',$smtp->log())===0,
			'CRLF injection in MAIL FROM is stripped'
		);
		// CRLF injection in RCPT TO is prevented
		$smtp=new \SMTP('localhost',25);
		$smtp->set('From','legit@example.com');
		$smtp->set('To',"victim@example.com\r\nBcc:attacker@evil.com");
		$smtp->set('Subject','Test');
		$smtp->send('Hello',TRUE,TRUE);
		$test->expect(
			preg_match('/\nBcc:attacker/',$smtp->log())===0,
			'CRLF injection in RCPT TO is stripped'
		);
		// TLS context defaults to verify_peer=TRUE
		$ctxProp=new \ReflectionProperty('SMTP','context');
		$smtp=new \SMTP('smtp.example.com',587,'tls');
		$ctx=stream_context_get_options($ctxProp->getValue($smtp));
		$test->expect(
			isset($ctx['ssl']['verify_peer']) && $ctx['ssl']['verify_peer']===TRUE,
			'TLS context enables verify_peer by default'
		);
		$test->expect(
			isset($ctx['ssl']['verify_peer_name']) && $ctx['ssl']['verify_peer_name']===TRUE,
			'TLS context enables verify_peer_name by default'
		);
		// Plain TCP gets no SSL context
		$smtp=new \SMTP('localhost',25);
		$ctx=stream_context_get_options($ctxProp->getValue($smtp));
		$test->expect(
			!isset($ctx['ssl']['verify_peer']),
			'Plain TCP gets no SSL context'
		);
		// User-provided context overrides defaults
		$smtp=new \SMTP('smtp.example.com',465,'ssl',NULL,NULL,
			['ssl'=>['verify_peer'=>FALSE]]);
		$ctx=stream_context_get_options($ctxProp->getValue($smtp));
		$test->expect(
			$ctx['ssl']['verify_peer']===FALSE,
			'User-provided context overrides defaults'
		);
		$f3->set('results',$test->results());
	}

}
