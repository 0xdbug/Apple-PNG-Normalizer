<?php

/**
 * APN - Apple PNG Normalizer
 *
 * This class provides methods to normalize a CgBI file, which is Apple's proprietary PNG extension. Most software can't read them.
 * They are normally found in IPA files.
 * It is based on the iPIN - iPhone PNG Images Normalizer v1.0 by Axel E. Brzostowski.
 *
 * @author dbug
 * @license GNU General Public License v3.0
 */



class APN
{
  /**
   * Normalize PNG image.
   *
   * @param string $imagePath Path to the PNG image file.
   * @return string|null The normalized PNG data, or null if the image is not a valid PNG.
   */
  public static function getNormalizedPNG(string $imagePath): ?string
  {
    $pngheader = "\x89PNG\r\n\x1a\n";

    $file = fopen($imagePath, "rb");
    $oldPNG = fread($file, filesize($imagePath));
    fclose($file);

    if (substr($oldPNG, 0, 8) !== $pngheader) {
      return null;
    }

    $newPNG = substr($oldPNG, 0, 8);

    $chunkPos = strlen($newPNG);

    $idatAcc = "";
    $breakLoop = false;

    while ($chunkPos < strlen($oldPNG)) {
      $skip = false;

      $chunkLength = unpack("N", substr($oldPNG, $chunkPos, 4))[1];
      $chunkType = substr($oldPNG, $chunkPos + 4, 4);
      $chunkData = substr($oldPNG, $chunkPos + 8, $chunkLength);
      $chunkCRC = unpack("N", substr($oldPNG, $chunkPos + $chunkLength + 8, 4))[1];
      $chunkPos += $chunkLength + 12;

      // Extract width and height from the IHDR chunk
      if ($chunkType === "IHDR") {
        $width = unpack("N", substr($chunkData, 0, 4))[1];
        $height = unpack("N", substr($chunkData, 4, 4))[1];
      }

      // Accumulate IDAT chunks
      if ($chunkType === "IDAT") {
        $idatAcc .= $chunkData;
        $skip = true;
      }

      // Skip the CgBI chunk
      if ($chunkType === "CgBI") {
        $skip = true;
      }

      // Process the last chunk (IEND)
      if ($chunkType === "IEND") {
        try {
          $bufSize = $width * $height * 4 + $height;
          $chunkData = zlib_decode($idatAcc, $bufSize);
        } catch (Exception $e) {
          echo $e->getMessage();
          return null;
        }

        $chunkType = "IDAT";

        $newData = "";
        for ($y = 0; $y < $height; $y++) {
          $i = strlen($newData);
          if (isset($chunkData[$i])) {
            $newData .= $chunkData[$i];
          }
          for ($x = 0; $x < $width; $x++) {
            $i = strlen($newData);
            if (isset($chunkData[$i + 2])) {
              $newData .= $chunkData[$i + 2];
            }
            if (isset($chunkData[$i + 1])) {
              $newData .= $chunkData[$i + 1];
            }
            if (isset($chunkData[$i])) {
              $newData .= $chunkData[$i];
            }
            if (isset($chunkData[$i + 3])) {
              $newData .= $chunkData[$i + 3];
            }
          }
        }

        $chunkData = $newData;
        $chunkData = zlib_encode($chunkData, ZLIB_ENCODING_DEFLATE);
        $chunkLength = strlen($chunkData);
        $chunkCRC = crc32($chunkType . $chunkData);
        $chunkCRC = ($chunkCRC + 0x100000000) % 0x100000000;
        $breakLoop = true;
      }

      if (!$skip) {
        $newPNG .= pack("N", $chunkLength);
        $newPNG .= $chunkType;
        if ($chunkLength > 0) {
          $newPNG .= $chunkData;
        }
        $newPNG .= pack("N", $chunkCRC);
      }
 
      if ($breakLoop) {
        break;
      }
    }
    return $newPNG;
  }

  /**
   * Update the PNG image file with the normalized PNG data.
   *
   * @param string $imagePath Path to the PNG image file.
   * @return bool True if the update is successful, false otherwise.
   */
  public static function updatePNG(string $imagePath): bool
  {
    $data = APN::getNormalizedPNG($imagePath);
    if ($data !== null) {
      $file = fopen($imagePath, "wb");
      fwrite($file, $data);
      fclose($file);
      return true;
    }
    return false;
  }
}