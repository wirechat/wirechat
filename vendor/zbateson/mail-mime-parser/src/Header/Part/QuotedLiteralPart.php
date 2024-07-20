<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header\Part;

/**
 * A quoted literal header string part.  The value of the part is stripped of CR
 * and LF characters, but otherwise not transformed or changed in any way.
 *
 * @author Zaahid Bateson
 */
class QuotedLiteralPart extends ContainerPart
{
    protected function filterIgnoredSpaces(array $parts) : array
    {
        return $parts;
    }
}
