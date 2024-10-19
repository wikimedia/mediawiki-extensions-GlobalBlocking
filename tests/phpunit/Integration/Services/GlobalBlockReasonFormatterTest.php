<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockReasonFormatter;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Status\Status;
use MediaWikiIntegrationTestCase;
use MWHttpRequest;
use Psr\Log\LoggerInterface;
use Wikimedia\ObjectCache\WANObjectCache;
use Wikimedia\TestingAccessWrapper;

/**
 * @author Taavi "Majavah" Väänänen <hi@taavi.wtf>
 * @covers MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockReasonFormatter
 */
class GlobalBlockReasonFormatterTest extends MediaWikiIntegrationTestCase {
	public function testConstructor() {
		$this->assertInstanceOf(
			GlobalBlockReasonFormatter::class,
			new GlobalBlockReasonFormatter(
				new ServiceOptions(
					GlobalBlockReasonFormatter::CONSTRUCTOR_OPTIONS,
					[
						'GlobalBlockRemoteReasonUrl' => null,
					]
				),
				$this->createMock( WANObjectCache::class ),
				$this->createMock( HttpRequestFactory::class ),
				$this->createMock( LoggerInterface::class )
			)
		);
	}

	public function testExpandWithoutUrl() {
		$formatter = TestingAccessWrapper::newFromObject(
			new GlobalBlockReasonFormatter(
				new ServiceOptions(
					GlobalBlockReasonFormatter::CONSTRUCTOR_OPTIONS,
					[
						'GlobalBlockRemoteReasonUrl' => null,
					]
				),
				$this->createMock( WANObjectCache::class ),
				$this->createNoOpMock( HttpRequestFactory::class ),
				$this->createMock( LoggerInterface::class )
			)
		);

		$this->assertEquals( 'foo {{bar}}', $formatter->expandRemoteTemplates( 'foo {{bar}}', 'en' ) );
	}

	public function testExpandHttpFailure() {
		$httpRequest = $this->createMock( MWHttpRequest::class );
		$httpRequest->expects( $this->once() )
			->method( 'execute' )
			->willReturn( Status::newFatal( wfMessage( 'test' ) ) );

		$httpRequestFactory = $this->createMock( HttpRequestFactory::class );
		$httpRequestFactory->expects( $this->once() )
			->method( 'getUserAgent' )
			->willReturn( 'bananas' );
		$httpRequestFactory->expects( $this->once() )
			->method( 'create' )
			->willReturn( $httpRequest );

		$logger = $this->createMock( LoggerInterface::class );
		$logger->expects( $this->once() )
			->method( 'error' );

		$formatter = TestingAccessWrapper::newFromObject(
			new GlobalBlockReasonFormatter(
				new ServiceOptions(
					GlobalBlockReasonFormatter::CONSTRUCTOR_OPTIONS,
					[
						'GlobalBlockRemoteReasonUrl' => 'http://foo.invalid/w/api.php',
					]
				),
				$this->createMock( WANObjectCache::class ),
				$httpRequestFactory,
				$logger
			)
		);

		$this->assertEquals( 'foo {{bar}}', $formatter->expandRemoteTemplates( 'foo {{bar}}', 'en' ) );
	}

	public function testExpandHttpWarning() {
		$httpRequest = $this->createMock( MWHttpRequest::class );
		$httpRequest->expects( $this->once() )
			->method( 'execute' )
			->willReturn( Status::newGood()->warning( wfMessage( 'test' ) ) );
		$httpRequest->expects( $this->once() )
			->method( 'getContent' )
			->willReturn( '{"expandtemplates": {"wikitext": "foo baz"}}' );

		$httpRequestFactory = $this->createMock( HttpRequestFactory::class );
		$httpRequestFactory->expects( $this->once() )
			->method( 'getUserAgent' )
			->willReturn( 'bananas' );
		$httpRequestFactory->expects( $this->once() )
			->method( 'create' )
			->willReturn( $httpRequest );

		$logger = $this->createMock( LoggerInterface::class );
		$logger->expects( $this->once() )
			->method( 'warning' );

		$formatter = TestingAccessWrapper::newFromObject(
			new GlobalBlockReasonFormatter(
				new ServiceOptions(
					GlobalBlockReasonFormatter::CONSTRUCTOR_OPTIONS,
					[
						'GlobalBlockRemoteReasonUrl' => 'http://foo.invalid/w/api.php',
					]
				),
				$this->createMock( WANObjectCache::class ),
				$httpRequestFactory,
				$logger
			)
		);

		$this->assertEquals( 'foo baz', $formatter->expandRemoteTemplates( 'foo {{bar}}', 'en' ) );
	}

	public function testExpandInvalidJson() {
		$httpRequest = $this->createMock( MWHttpRequest::class );
		$httpRequest->expects( $this->once() )
			->method( 'execute' )
			->willReturn( Status::newGood() );
		$httpRequest->expects( $this->once() )
			->method( 'getContent' )
			->willReturn( '{"error": "nope"}' );

		$httpRequestFactory = $this->createMock( HttpRequestFactory::class );
		$httpRequestFactory->expects( $this->once() )
			->method( 'getUserAgent' )
			->willReturn( 'bananas' );
		$httpRequestFactory->expects( $this->once() )
			->method( 'create' )
			->willReturn( $httpRequest );

		$logger = $this->createMock( LoggerInterface::class );
		$logger->expects( $this->once() )
			->method( 'warning' );

		$formatter = TestingAccessWrapper::newFromObject(
			new GlobalBlockReasonFormatter(
				new ServiceOptions(
					GlobalBlockReasonFormatter::CONSTRUCTOR_OPTIONS,
					[
						'GlobalBlockRemoteReasonUrl' => 'http://foo.invalid/w/api.php',
					]
				),
				$this->createMock( WANObjectCache::class ),
				$httpRequestFactory,
				$logger
			)
		);

		$this->assertEquals( 'foo {{bar}}', $formatter->expandRemoteTemplates( 'foo {{bar}}', 'en' ) );
	}

	public function testExpandWorksFine() {
		$httpRequest = $this->createMock( MWHttpRequest::class );
		$httpRequest->expects( $this->once() )
			->method( 'execute' )
			->willReturn( Status::newGood() );
		$httpRequest->expects( $this->once() )
			->method( 'getContent' )
			->willReturn( '{"expandtemplates": {"wikitext": "foo baz"}}' );

		$httpRequestFactory = $this->createMock( HttpRequestFactory::class );
		$httpRequestFactory->expects( $this->once() )
			->method( 'getUserAgent' )
			->willReturn( 'bananas' );
		$httpRequestFactory->expects( $this->once() )
			->method( 'create' )
			->willReturn( $httpRequest );

		$formatter = TestingAccessWrapper::newFromObject(
			new GlobalBlockReasonFormatter(
				new ServiceOptions(
					GlobalBlockReasonFormatter::CONSTRUCTOR_OPTIONS,
					[
						'GlobalBlockRemoteReasonUrl' => 'http://foo.invalid/w/api.php',
					]
				),
				$this->createMock( WANObjectCache::class ),
				$httpRequestFactory,
				$this->createNoOpMock( LoggerInterface::class )
			)
		);

		$this->assertEquals( 'foo baz', $formatter->expandRemoteTemplates( 'foo {{bar}}', 'en' ) );
	}
}
