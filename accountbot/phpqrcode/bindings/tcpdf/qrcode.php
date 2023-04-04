<?php
//============================================================+
// File name   : qrcode.php
// Begin       : 2010-03-22
// Last Update : 2010-03-29
// Version     : 1.0.002
// License     : GNU LGPL v.3 (http://www.gnu.org/copyleft/lesser.html)
// 	----------------------------------------------------------------------------
//
// 	This library is free software; you can redistribute it and/or
// 	modify it under the terms of the GNU Lesser General Public
// 	License as published by the Free Software Foundation; either
// 	version 3 of the License, or any later version.
//
// 	This library is distributed in the hope that it will be useful,
// 	but WITHOUT ANY WARRANTY; without even the implied warranty of
// 	MERCHANTABILITY o

		/**
		 * Return new bitstream from bytes
		 * @param int $size size
		 * @param array $data bytes
		 * @return array bitstream
		 */
		 protected function newFromBytes($size, $data) {
			$bstream = $this->allocate($size * 8);
			$p=0;
			for ($i=0; $i<$size; ++$i) {
				$mask = 0x80;
				for ($j=0; $j<8; ++$j) {
					if ($data[$i] & $mask) {
						$bstream[$p] = 1;
					} else {
						$bstream[$p] = 0;
					}
					$p++;
					$mask = $mask >> 1;
				}
			}
			return $bstream;
		}

		/**
		 * Append one bitstream to another
		 * @param array $bitstream original bitstream
		 * @param array $append bitstream to append
		 * @return array bitstream
		 */
		 protected function appendBitstream($bitstream, $append) {
			if ((!is_array($append)) OR (count($append) == 0)) {
				return $bitstream;
			}
			if (count($bitstream) == 0) {
				return $append;
			}
			return array_values(array_merge($bitstream, $append));
		}

		/**
		 * Append one bitstream created from number to another
		 * @param array $bitstream original bitstream
		 * @param int $bits number of bits
		 * @param int $num number
		 * @return array bitstream
		 */
		 protected function appendNum($bitstream, $bits, $num) {
			if ($bits == 0) {
				return 0;
			}
			$b = $this->newFromNum($bits, $num);
			return $this->appendBitstream($bitstream, $b);
		}

		/**
		 * Append one bitstream created from bytes to another
		 * @param array $bitstream original bitstream
		 * @param int $size size
		 * @param array $data bytes
		 * @return array bitstream
		 */
		 protected function appendBytes($bitstream, $size, $data) {
			if ($size == 0) {
				return 0;
			}
			$b = $this->newFromBytes($size, $data);
			return $this->appendBitstream($bitstream, $b);
		}

		/**
		 * Convert bitstream to bytes
		 * @param array $bitstream original bitstream
		 * @return array of bytes
		 */
		 protected function bitstreamToByte($bstream) {
			$size = count($bstream);
			if ($size == 0) {
				return array();
			}
			$data = array_fill(0, (int)(($size + 7) / 8), 0);
			$bytes = (int)($size / 8);
			$p = 0;
			for ($i=0; $i<$bytes; $i++) {
				$v = 0;
				for ($j=0; $j<8; $j++) {
					$v = $v << 1;
					$v |= $bstream[$p];
					$p++;
				}
				$data[$i] = $v;
			}
			if ($size & 7) {
				$v = 0;
				for ($j=0; $j<($size & 7); $j++) {
					$v = $v << 1;
					$v |= $bstream[$p];
					$p++;
				}
				$data[$bytes] = $v;
			}
			return $data;
		}

		// - - - - - - - - - - - - - - - - - - - - - - - - -

		// QRspec

		/**
		 * Replace a value on the array at the specified position
		 * @param array $srctab
		 * @param int $x X position
		 * @param int $y Y position
		 * @param string $repl value to replace
		 * @param int $replLen length of the repl string
		 * @return array srctab
		 */
		 protected function qrstrset($srctab, $x, $y, $repl, $replLen=false) {
			$srctab[$y] = substr_replace($srctab[$y], ($replLen !== false)?substr($repl,0,$replLen):$repl, $x, ($replLen !== false)?$replLen:strlen($repl));
			return $srctab;
		}

		/**
		 * Return maximum data code length (bytes) for the version.
		 * @param int $version version
		 * @param int $level error correction level
		 * @return int maximum size (bytes)
		 */
		protected function getDataLength($version, $level) {
			return $this->capacity[$version][QRCAP_WORDS] - $this->capacity[$version][QRCAP_EC][$level];
		}

		/**
		 * Return maximum error correction code length (bytes) for the version.
		 * @param int $version version
		 * @param int $level error correction level
		 * @return int ECC size (bytes)
		 */
		protected function getECCLength($version, $level){
			return $this->capacity[$version][QRCAP_EC][$level];
		}

		/**
		 * Return the width of the symbol for the version.
		 * @param int $version version
		 * @return int width
		 */
		protected function getWidth($version) {
			return $this->capacity[$version][QRCAP_WIDTH];
		}

		/**
		 * Return the numer of remainder bits.
		 * @param int $version version
		 * @return int number of remainder bits
		 */
		protected function getRemainder($version) {
			return $this->capacity[$version][QRCAP_REMINDER];
		}

		/**
		 * Return a version number that satisfies the input code length.
		 * @param int $size input code length (byte)
		 * @param int $level error correction level
		 * @return int version number
		 */
		protected function getMinimumVersion($size, $level) {
			for ($i=1; $i <= QRSPEC_VERSION_MAX; ++$i) {
				$words  = $this->capacity[$i][QRCAP_WORDS] - $this->capacity[$i][QRCAP_EC][$level];
				if ($words >= $size) {
					return $i;
				}
			}
			return -1;
		}

		/**
		 * Return the size of length indicator for the mode and version.
		 * @param int $mode encoding mode
		 * @param int $version version
		 * @return int the size of the appropriate length indicator (bits).
		 */
		protected function lengthIndicator($mode, $version) {
			if ($mode == QR_MODE_ST) {
				return 0;
			}
			if ($version <= 9) {
				$l = 0;
			} elseif ($version <= 26) {
				$l = 1;
			} else {
				$l = 2;
			}
			return $this->lengthTableBits[$mode][$l];
		}

		/**
		 * Return the maximum length for the mode and version.
		 * @param int $mode encoding mode
		 * @param int $version version
		 * @return int the maximum length (bytes)
		 */
		protected function maximumWords($mode, $version) {
			if ($mode == QR_MODE_ST) {
				return 3;
			}
			if ($version <= 9) {
				$l = 0;
			} else if ($version <= 26) {
				$l = 1;
			} else {
				$l = 2;
			}
			$bits = $this->lengthTableBits[$mode][$l];
			$words = (1 << $bits) - 1;
			if ($mode == QR_MODE_KJ) {
				$words *= 2; // the number of bytes is required
			}
			return $words;
		}

		/**
		 * Return an array of ECC specification.
		 * @param int $version version
		 * @param int $level error correction level
		 * @param array $spec an array of ECC specification contains as following: {# of type1 blocks, # of data code, # of ecc code, # of type2 blocks, # of data code}
		 * @return array spec
		 */
		protected function getEccSpec($version, $level, $spec) {
			if (count($spec) < 5) {
				$spec = array(0, 0, 0, 0, 0);
			}
			$b1 = $this->eccTable[$version][$level][0];
			$b2 = $this->eccTable[$version][$level][1];
			$data = $this->getDataLength($version, $level);
			$ecc = $this->getECCLength($version, $level);
			if ($b2 == 0) {
				$spec[0] = $b1;
				$spec[1] = (int)($data / $b1);
				$spec[2] = (int)($ecc / $b1);
				$spec[3] = 0;
				$spec[4] = 0;
			} else {
				$spec[0] = $b1;
				$spec[1] = (int)($data / ($b1 + $b2));
				$spec[2] = (int)($ecc  / ($b1 + $b2));
				$spec[3] = $b2;
				$spec[4] = $spec[1] + 1;
			}
			return $spec;
		}

		/**
		 * Put an alignment marker.
		 * @param array $frame frame
		 * @param int $width width
		 * @param int $ox X center coordinate of the pattern
		 * @param int $oy Y center coordinate of the pattern
		 * @return array frame
		 */
		protected function putAlignmentMarker($frame, $ox, $oy) {
			$finder = array(
				"\xa1\xa1\xa1\xa1\xa1",
				"\xa1\xa0\xa0\xa0\xa1",
				"\xa1\xa0\xa1\xa0\xa1",
				"\xa1\xa0\xa0\xa0\xa1",
				"\xa1\xa1\xa1\xa1\xa1"
				);
			$yStart = $oy - 2;
			$xStart = $ox - 2;
			for ($y=0; $y < 5; $y++) {
				$frame = $this->qrstrset($frame, $xStart, $yStart+$y, $finder[$y]);
			}
			return $frame;
		}

		/**
		 * Put an alignment pattern.
		 * @param int $version version
		 * @param array $fram frame
		 * @param int $width width
		 * @return array frame
		 */
		 protected function putAlignmentPattern($version, $frame, $width) {
			if ($version < 2) {
				return $frame;
			}
			$d = $this->alignmentPattern[$version][1] - $this->alignmentPattern[$version][0];
			if ($d < 0) {
				$w = 2;
			} else {
				$w = (int)(($width - $this->alignmentPattern[$version][0]) / $d + 2);
			}
			if ($w * $w - 3 == 1) {
				$x = $this->alignmentPattern[$version][0];
				$y = $this->alignmentPattern[$version][0];
				$frame = $this->putAlignmentMarker($frame, $x, $y);
				return $frame;
			}
			$cx = $this->alignmentPattern[$version][0];
			$wo = $w - 1;
			for ($x=1; $x < $wo; ++$x) {
				$frame = $this->putAlignmentMarker($frame, 6, $cx);
				$frame = $this->putAlignmentMarker($frame, $cx,  6);
				$cx += $d;
			}
			$cy = $this->alignmentPattern[$version][0];
			for ($y=0; $y < $wo; ++$y) {
				$cx = $this->alignmentPattern[$version][0];
				for ($x=0; $x < $wo; ++$x) {
					$frame = $this->putAlignmentMarker($frame, $cx, $cy);
					$cx += $d;
				}
				$cy += $d;
			}
			return $frame;
		}

		/**
		 * Return BCH encoded version information pattern that is used for the symbol of version 7 or greater. Use lower 18 bits.
		 * @param int $version version
		 * @return BCH encoded version information pattern
		 */
		protected function getVersionPattern($version) {
			if (($version < 7) OR ($version > QRSPEC_VERSION_MAX)) {
				return 0;
			}
			return $this->versionPattern[($version - 7)];
		}

		/**
		 * Return BCH encoded format information pattern.
		 * @param array $mask
		 * @param int $level error correction level
		 * @return BCH encoded format information pattern
		 */
		protected function getFormatInfo($mask, $level) {
			if (($mask < 0) OR ($mask > 7)) {
				return 0;
			}
			if (($level < 0) OR ($level > 3)) {
				return 0;
			}
			return $this->formatInfo[$level][$mask];
		}

		/**
		 * Put a finder pattern.
		 * @param array $frame frame
		 * @param int $width width
		 * @param int $ox X center coordinate of the pattern
		 * @param int $oy Y center coordinate of the pattern
		 * @return array frame
		 */
		protected function putFinderPattern($frame, $ox, $oy) {
			$finder = array(
			"\xc1\xc1\xc1\xc1\xc1\xc1\xc1",
			"\xc1\xc0\xc0\xc0\xc0\xc0\xc1",
			"\xc1\xc0\xc1\xc1\xc1\xc0\xc1",
			"\xc1\xc0\xc1\xc1\xc1\xc0\xc1",
			"\xc1\xc0\xc1\xc1\xc1\xc0\xc1",
			"\xc1\xc0\xc0\xc0\xc0\xc0\xc1",
			"\xc1\xc1\xc1\xc1\xc1\xc1\xc1"
			);
			for ($y=0; $y < 7; $y++) {
				$frame = $this->qrstrset($frame, $ox, ($oy + $y), $finder[$y]);
			}
			return $frame;
		}

		/**
		 * Return a copy of initialized frame.
		 * @param int $version version
		 * @return Array of unsigned char.
		 */
		protected function createFrame($version) {
			$width = $this->capacity[$version][QRCAP_WIDTH];
			$frameLine = str_repeat ("\0", $width);
			$frame = array_fill(0, $width, $frameLine);
			// Finder pattern
			$frame = $this->putFinderPattern($frame, 0, 0);
			$frame = $this->putFinderPattern($frame, $width - 7, 0);
			$frame = $this->putFinderPattern($frame, 0, $width - 7);
			// Separator
			$yOffset = $width - 7;
			for ($y=0; $y < 7; ++$y) {
				$frame[$y][7] = "\xc0";
				$frame[$y][$width - 8] = "\xc0";
				$frame[$yOffset][7] = "\xc0";
				++$yOffset;
			}
			$setPattern = str_repeat("\xc0", 8);
			$frame = $this->qrstrset($frame, 0, 7, $setPattern);
			$frame = $this->qrstrset($frame, $width-8, 7, $setPattern);
			$frame = $this->qrstrset($frame, 0, $width - 8, $setPattern);
			// Format info
			$setPattern = str_repeat("\x84", 9);
			$frame = $this->qrstrset($frame, 0, 8, $setPattern);
			$frame = $this->qrstrset($frame, $width - 8, 8, $setPattern, 8);
			$yOffset = $width - 8;
			for ($y=0; $y < 8; ++$y,++$yOffset) {
				$frame[$y][8] = "\x84";
				$frame[$yOffset][8] = "\x84";
			}
			// Timing pattern
			$wo = $width - 15;
			for ($i=1; $i < $wo; ++$i) {
				$frame[6][7+$i] = chr(0x90 | ($i & 1));
				$frame[7+$i][6] = chr(0x90 | ($i & 1));
			}
			// Alignment pattern
			$frame = $this->putAlignmentPattern($version, $frame, $width);
			// Version information
			if ($version >= 7) {
				$vinf = $this->getVersionPattern($version);
				$v = $vinf;
				for ($x=0; $x<6; ++$x) {
					for ($y=0; $y<3; ++$y) {
						$frame[($width - 11)+$y][$x] = chr(0x88 | ($v & 1));
						$v = $v >> 1;
					}
				}
				$v = $vinf;
				for ($y=0; $y<6; ++$y) {
					for ($x=0; $x<3; ++$x) {
						$frame[$y][$x+($width - 11)] = chr(0x88 | ($v & 1));
						$v = $v >> 1;
					}
				}
			}
			// and a little bit...
			$frame[$width - 8][8] = "\x81";
			return $frame;
		}

		/**
		 * Set new frame for the specified version.
		 * @param int $version version
		 * @return Array of unsigned char.
		 */
		protected function newFrame($version) {
			if (($version < 1) OR ($version > QRSPEC_VERSION_MAX)) {
				return NULL;
			}
			if (!isset($this->frames[$version])) {
				$this->frames[$version] = $this->createFrame($version);
			}
			if (is_null($this->frames[$version])) {
				return NULL;
			}
			return $this->frames[$version];
		}

		/**
		 * Return block number 0
		 * @param array $spec
		 * @return int value
		 */
		 protected function rsBlockNum($spec) {
			return ($spec[0] + $spec[3]);
		}

		/**
		* Return block number 1
		 * @param array $spec
		 * @return int value
		 */
		 protected function rsBlockNum1($spec) {
			return $spec[0];
		}

		/**
		 * Return data codes 1
		 * @param array $spec
		 * @return int value
		 */
		 protected function rsDataCodes1($spec) {
			return $spec[1];
		}

		/**
		 * Return ecc codes 1
		 * @param array $spec
		 * @return int value
		 */
		 protected function rsEccCodes1($spec) {
			return $spec[2];
		}

		/**
		 * Return block number 2
		 * @param array $spec
		 * @return int value
		 */
		 protected function rsBlockNum2($spec) {
			return $spec[3];
		}

		/**
		 * Return data codes 2
		 * @param array $spec
		 * @return int value
		 */
		 protected function rsDataCodes2($spec) {
			return $spec[4];
		}

		/**
		 * Return ecc codes 2
		 * @param array $spec
		 * @return int value
		 */
		 protected function rsEccCodes2($spec) {
			return $spec[2];
		}

		/**
		 * Return data length
		 * @param array $spec
		 * @return int value
		 */
		 protected function rsDataLength($spec) {
			return ($spec[0] * $spec[1]) + ($spec[3] * $spec[4]);
		}

		/**
		 * Return ecc length
		 * @param array $spec
		 * @return int value
		 */
		 protected function rsEccLength($spec) {
			return ($spec[0] + $spec[3]) * $spec[2];
		}

		// - - - - - - - - - - - - - - - - - - - - - - - - -

		// QRrs

		/**
		 * Initialize a Reed-Solomon codec and add it to existing rsitems
		 * @param int $symsize symbol size, bits
		 * @param int $gfpoly  Field generator polynomial coefficients
		 * @param int $fcr  first root of RS code generator polynomial, index form
		 * @param int $prim  primitive element to generate polynomial roots
		 * @param int $nroots RS code generator polynomial degree (number of roots)
		 * @param int $pad  padding bytes at front of shortened block
		 * @return array Array of RS values:<ul><li>mm = Bits per symbol;</li><li>nn = Symbols per block;</li><li>alpha_to = log lookup table array;</li><li>index_of = Antilog lookup table array;</li><li>genpoly = Generator polynomial array;</li><li>nroots = Number of generator;</li><li>roots = number of parity symbols;</li><li>fcr = First consecutive root, index form;</li><li>prim = Primitive element, index form;</li><li>iprim = prim-th root of 1, index form;</li><li>pad = Padding bytes in shortened block;</li><li>gfpoly</ul>.
		 */
		 protected function init_rs($symsize, $gfpoly, $fcr, $prim, $nroots, $pad) {
			foreach ($this->rsitems as $rs) {
				if (($rs['pad'] != $pad) OR ($rs['nroots'] != $nroots) OR ($rs['mm'] != $symsize)
					OR ($rs['gfpoly'] != $gfpoly) OR ($rs['fcr'] != $fcr) OR ($rs['prim'] != $prim)) {
					continue;
				}
				return $rs;
			}
			$rs = $this->init_rs_char($symsize, $gfpoly, $fcr, $prim, $nroots, $pad);
			array_unshift($this->rsitems, $rs);
			return $rs;
		}

		// - - - - - - - - - - - - - - - - - - - - - - - - -

		// QRrsItem

		/**
		 * modnn
		 * @param array RS values
		 * @param int $x X position
		 * @return int X osition
		 */
		 protected function modnn($rs, $x) {
			while ($x >= $rs['nn']) {
				$x -= $rs['nn'];
				$x = ($x >> $rs['mm']) + ($x & $rs['nn']);
			}
			return $x;
		}

		/**
		 * Initialize a Reed-Solomon codec and returns an array of values.
		 * @param int $symsize symbol size, bits
		 * @param int $gfpoly  Field generator polynomial coefficients
		 * @param int $fcr  first root of RS code generator polynomial, index form
		 * @param int $prim  primitive element to generate polynomial roots
		 * @param int $nroots RS code generator polynomial degree (number of roots)
		 * @param int $pad  padding bytes at front of shortened block
		 * @return array Array of RS values:<ul><li>mm = Bits per symbol;</li><li>nn = Symbols per block;</li><li>alpha_to = log lookup table array;</li><li>index_of = Antilog lookup table array;</li><li>genpoly = Generator polynomial array;</li><li>nroots = Number of generator;</li><li>roots = number of parity symbols;</li><li>fcr = First consecutive root, index form;</li><li>prim = Primitive element, index form;</li><li>iprim = prim-th root of 1, index form;</li><li>pad = Padding bytes in shortened block;</li><li>gfpoly</ul>.
		 */
		protected function init_rs_char($symsize, $gfpoly, $fcr, $prim, $nroots, $pad) {
			// Based on Reed solomon encoder by Phil Karn, KA9Q (GNU-LGPLv2)
			$rs = null;
			// Check parameter ranges
			if (($symsize < 0) OR ($symsize > 8)) {
				return $rs;
			}
			if (($fcr < 0) OR ($fcr >= (1<<$symsize))) {
				return $rs;
			}
			if (($prim <= 0) OR ($prim >= (1<<$symsize))) {
				return $rs;
			}
			if (($nroots < 0) OR ($nroots >= (1<<$symsize))) {
				return $rs;
			}
			if (($pad < 0) OR ($pad >= ((1<<$symsize) -1 - $nroots))) {
				return $rs;
			}
			$rs = array();
			$rs['mm'] = $symsize;
			$rs['nn'] = (1 << $symsize) - 1;
			$rs['pad'] = $pad;
			$rs['alpha_to'] = array_fill(0, ($rs['nn'] + 1), 0);
			$rs['index_of'] = array_fill(0, ($rs['nn'] + 1), 0);
			// PHP style macro replacement ;)
			$NN =& $rs['nn'];
			$A0 =& $NN;
			// Generate Galois field lookup tables
			$rs['index_of'][0] = $A0; // log(zero) = -inf
			$rs['alpha_to'][$A0] = 0; // alpha**-inf = 0
			$sr = 1;
			for ($i=0; $i<$rs['nn']; ++$i) {
				$rs['index_of'][$sr] = $i;
				$rs['alpha_to'][$i] = $sr;
				$sr <<= 1;
				if ($sr & (1 << $symsize)) {
					$sr ^= $gfpoly;
				}
				$sr &= $rs['nn'];
			}
			if ($sr != 1) {
				// field generator polynomial is not primitive!
				return NULL;
			}
			// Form RS code generator polynomial from its roots
			$rs['genpoly'] = array_fill(0, ($nroots + 1), 0);
			$rs['fcr'] = $fcr;
			$rs['prim'] = $prim;
			$rs['nroots'] = $nroots;
			$rs['gfpoly'] = $gfpoly;
			// Find prim-th root of 1, used in decoding
			for ($iprim=1; ($iprim % $prim) != 0; $iprim += $rs['nn']) {
				; // intentional empty-body loop!
			}
			$rs['iprim'] = (int)($iprim / $prim);
			$rs['genpoly'][0] = 1;


			for ($i = 0,$root=$fcr*$prim; $i < $nroots; $i++, $root += $prim) {
				$rs['genpoly'][$i+1] = 1;
				// Multiply rs->genpoly[] by  @**(root + x)
				for ($j = $i; $j > 0; --$j) {
					if ($rs['genpoly'][$j] != 0) {
						$rs['genpoly'][$j] = $rs['genpoly'][$j-1] ^ $rs['alpha_to'][$this->modnn($rs, $rs['index_of'][$rs['genpoly'][$j]] + $root)];
					} else {
						$rs['genpoly'][$j] = $rs['genpoly'][$j-1];
					}
				}
				// rs->genpoly[0] can never be zero
				$rs['genpoly'][0] = $rs['alpha_to'][$this->modnn($rs, $rs['index_of'][$rs['genpoly'][0]] + $root)];
			}
			// convert rs->genpoly[] to index form for quicker encoding
			for ($i = 0; $i <= $nroots; ++$i) {
				$rs['genpoly'][$i] = $rs['index_of'][$rs['genpoly'][$i]];
			}
			return $rs;
		}

		/**
		 * Encode a Reed-Solomon codec and returns the parity array
		 * @param array $rs RS values
		 * @param array $data data
		 * @param array $parity parity
		 * @return parity array
		 */
		 protected function encode_rs_char($rs, $data, $parity) {
			$MM       =& $rs['mm']; // bits per symbol
			$NN       =& $rs['nn']; // the total number of symbols in a RS block
			$ALPHA_TO =& $rs['alpha_to']; // the address of an array of NN elements to convert Galois field elements in index (log) form to polynomial form
			$INDEX_OF =& $rs['index_of']; // the address of an array of NN elements to convert Galois field elements in polynomial form to index (log) form
			$GENPOLY  =& $rs['genpoly']; // an array of NROOTS+1 elements containing the generator polynomial in index form
			$NROOTS   =& $rs['nroots']; // the number of roots in the RS code generator polynomial, which is the same as the number of parity symbols in a block
			$FCR      =& $rs['fcr']; // first consecutive root, index form
			$PRIM     =& $rs['prim']; // primitive element, index form
			$IPRIM    =& $rs['iprim']; // prim-th root of 1, index form
			$PAD      =& $rs['pad']; // the number of pad symbols in a block
			$A0       =& $NN;
			$parity = array_fill(0, $NROOTS, 0);
			for ($i=0; $i < ($NN - $NROOTS - $PAD); $i++) {
				$feedback = $INDEX_OF[$data[$i] ^ $parity[0]];
				if ($feedback != $A0) {
					// feedback term is non-zero
					// This line is unnecessary when GENPOLY[NROOTS] is unity, as it must
					// always be for the polynomials constructed by init_rs()
					$feedback = $this->modnn($rs, $NN - $GENPOLY[$NROOTS] + $feedback);
					for ($j=1; $j < $NROOTS; ++$j) {
					$parity[$j] ^= $ALPHA_TO[$this->modnn($rs, $feedback + $GENPOLY[($NROOTS - $j)])];
					}
				}
				// Shift
				array_shift($parity);
				if ($feedback != $A0) {
					array_push($parity, $ALPHA_TO[$this->modnn($rs, $feedback + $GENPOLY[0])]);
				} else {
					array_push($parity, 0);
				}
			}
			return $parity;
		}

	} // end QRcode class

} // END OF "class_exists QRcode"
?>
