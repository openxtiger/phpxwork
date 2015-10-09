<?php if(!defined('MS_XTIGER')) exit('Access Denied');
/**
 * Created by openXtiger.org.
 * User: xtiger
 * Date: 2009-6-15
 * Time: 21:30:17
 */
class CXTUnzip {
        var $Comment = '';

        var $Entries = array();

        var $Name = '';

        var $Size = 0;

        var $Time = 0;

        function SimpleUnzip($in_FileName = '') {
            if($in_FileName !== '') {
                CXT_Unzip::ReadFile($in_FileName);
            }
        } // end of the 'SimpleUnzip' constructor

        function Count() {
            return count($this->Entries);
        } // end of the 'Count()' method

        function GetData($in_Index) {
            return $this->Entries[$in_Index]->Data;
        } // end of the 'GetData()' method

        function GetEntry($in_Index) {
            return $this->Entries[$in_Index];
        } // end of the 'GetEntry()' method

        function GetError($in_Index) {
            return $this->Entries[$in_Index]->Error;
        } // end of the 'GetError()' method

        function GetErrorMsg($in_Index) {
            return $this->Entries[$in_Index]->ErrorMsg;
        } // end of the 'GetErrorMsg()' method

        function GetName($in_Index) {
            return $this->Entries[$in_Index]->Name;
        } // end of the 'GetName()' method

        function GetPath($in_Index) {
            return $this->Entries[$in_Index]->Path;
        } // end of the 'GetPath()' method

        function GetTime($in_Index) {
            return $this->Entries[$in_Index]->Time;
        } // end of the 'GetTime()' method

        function ReadFile($in_FileName) {
            $this->Entries = array();

            // Get file parameters
            $this->Name = $in_FileName;
            $this->Time = filemtime($in_FileName);
            $this->Size = filesize($in_FileName);

            // Read file
            $oF = fopen($in_FileName, 'rb');
            $vZ = fread($oF, $this->Size);
            fclose($oF);

            // Cut end of central directory
            $aE = explode("\x50\x4b\x05\x06", $vZ);

            // Easiest way, but not sure if format changes
            //$this->Comment = substr($aE[1], 18);

            // Normal way
            $aP = unpack('x16/v1CL', $aE[1]);
            $this->Comment = substr($aE[1], 18, $aP['CL']);

            // Translates end of line from other operating systems
            $this->Comment = strtr($this->Comment, array("\r\n" => "\n",
                                                         "\r"   => "\n"));

            // Cut the entries from the central directory
            $aE = explode("\x50\x4b\x01\x02", $vZ);
            // Explode to each part
            $aE = explode("\x50\x4b\x03\x04", $aE[0]);
            // Shift out spanning signature or empty entry
            array_shift($aE);

            // Loop through the entries
            foreach($aE as $vZ) {
                $aI = array();
                $aI['E']  = 0;
                $aI['EM'] = '';
                // Retrieving local file header information
                $aP = unpack('v1VN/v1GPF/v1CM/v1FT/v1FD/V1CRC/V1CS/V1UCS/v1FNL', $vZ);
                // Check if data is encrypted
                $bE = ($aP['GPF'] && 0x0001) ? TRUE : FALSE;
                $nF = $aP['FNL'];

                // Special case : value block after the compressed data
                if($aP['GPF'] & 0x0008) {
                    $aP1 = unpack('V1CRC/V1CS/V1UCS', substr($vZ, -12));

                    $aP['CRC'] = $aP1['CRC'];
                    $aP['CS']  = $aP1['CS'];
                    $aP['UCS'] = $aP1['UCS'];

                    $vZ = substr($vZ, 0, -12);
                }

                // Getting stored filename
                $aI['N'] = substr($vZ, 26, $nF);

                if(substr($aI['N'], -1) == '/') {
                    // is a directory entry - will be skipped
                    continue;
                }

                // Truncate full filename in path and filename
                $aI['P'] = dirname($aI['N']);
                $aI['P'] = $aI['P'] == '.' ? '' : $aI['P'];
                $aI['N'] = basename($aI['N']);

                $vZ = substr($vZ, 26 + $nF);

                if(strlen($vZ) != $aP['CS']) {
                  $aI['E']  = 1;
                  $aI['EM'] = 'Compressed size is not equal with the value in header information.';
                } else {
                    if($bE) {
                        $aI['E']  = 5;
                        $aI['EM'] = 'File is encrypted, which is not supported from this class.';
                    } else {
                        switch($aP['CM']) {
                            case 0: // Stored
                                // Here is nothing to do, the file ist flat.
                                break;

                            case 8: // Deflated
                                $vZ = gzinflate($vZ);
                                break;

                            case 12: // BZIP2
                                if(! extension_loaded('bz2')) {
                                    if(strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
                                      @dl('php_bz2.dll');
                                    } else {
                                      @dl('bz2.so');
                                    }
                                }

                                if(extension_loaded('bz2')) {
                                    $vZ = bzdecompress($vZ);
                                } else {
                                    $aI['E']  = 7;
                                    $aI['EM'] = "PHP BZIP2 extension not available.";
                                }

                                break;

                            default:
                              $aI['E']  = 6;
                              $aI['EM'] = "De-/Compression method {$aP['CM']} is not supported.";
                        }

                        if(! $aI['E']) {
                            if($vZ === FALSE) {
                                $aI['E']  = 2;
                                $aI['EM'] = 'Decompression of data failed.';
                            } else {
                                if(strlen($vZ) != $aP['UCS']) {
                                    $aI['E']  = 3;
                                    $aI['EM'] = 'Uncompressed size is not equal with the value in header information.';
                                } else {
                                    if(crc32($vZ) != $aP['CRC']) {
                                        $aI['E']  = 4;
                                        $aI['EM'] = 'CRC32 checksum is not equal with the value in header information.';
                                    }
                                }
                            }
                        }
                    }
                }

                $aI['D'] = $vZ;

                // DOS to UNIX timestamp
                $aI['T'] = mktime(($aP['FT']  & 0xf800) >> 11,
                                  ($aP['FT']  & 0x07e0) >>  5,
                                  ($aP['FT']  & 0x001f) <<  1,
                                  ($aP['FD']  & 0x01e0) >>  5,
                                  ($aP['FD']  & 0x001f),
                                  (($aP['FD'] & 0xfe00) >>  9) + 1980);

                $this->Entries[] = new CXT_UnzipEntry($aI);
            } // end for each entries

            return $this->Entries;
	} // end of the 'ReadFile()' method
} // end of the 'SimpleUnzip' class

class CXT_UnzipEntry {
        var $Data = '';

        var $Error = 0;

        var $ErrorMsg = '';

        var $Name = '';

        var $Path = '';

        var $Time = 0;

        function CXT_UnzipEntry($in_Entry) {
		$this->Data     = $in_Entry['D'];
		$this->Error    = $in_Entry['E'];
		$this->ErrorMsg = $in_Entry['EM'];
		$this->Name     = $in_Entry['N'];
		$this->Path     = $in_Entry['P'];
		$this->Time     = $in_Entry['T'];
        } // end of the 'SimpleUnzipEntry' constructor
} // end of the 'SimpleUnzipEntry' class
?>