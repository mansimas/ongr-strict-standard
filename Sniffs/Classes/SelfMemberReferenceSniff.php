<?php
/**
 * ONGR_Sniffs_Classes_ClassFileNameSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

namespace ONGR\Sniffs\Classes;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Standards_AbstractScopeSniff;

/**
 * Tests self member references.
 *
 * Verifies that :
 * <ul>
 *  <li>self:: is used instead of Self::</li>
 *  <li>self:: is used for local static member reference</li>
 *  <li>self:: is used instead of self ::</li>
 * </ul>
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class SelfMemberReferenceSniff extends PHP_CodeSniffer_Standards_AbstractScopeSniff
{
    /**
     * Constructs a ONGR_Sniffs_Classes_SelfMemberReferenceSniff.
     */
    public function __construct()
    {
        parent::__construct([T_CLASS], [T_DOUBLE_COLON]);
    }//end __construct()

    /**
     * Processes the function tokens within the class.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file where this token was found.
     * @param int                  $stackPtr  The position where the token was found.
     * @param int                  $currScope The current scope opener token.
     *
     * @return void
     */
    protected function processTokenWithinScope(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $currScope)
    {
        $tokens = $phpcsFile->getTokens();

        $calledClassName = ($stackPtr - 1);
        if ($tokens[$calledClassName]['code'] === T_SELF) {
            if (strtolower($tokens[$calledClassName]['content']) !== $tokens[$calledClassName]['content']) {
                $error = 'Must use "self::" for local static member reference; found "%s::"';
                $data = [$tokens[$calledClassName]['content']];
                $phpcsFile->addError($error, $calledClassName, 'IncorrectCase', $data);

                return;
            }
        } elseif ($tokens[$calledClassName]['code'] === T_STRING) {
            // If the class is called with a namespace prefix, build fully qualified
            // namespace calls for both current scope class and requested class.
            if ($tokens[($calledClassName - 1)]['code'] === T_NS_SEPARATOR) {
                $declarationName = $this->getDeclarationNameWithNamespace($tokens, $calledClassName);
                $declarationName = substr($declarationName, 1);
                $fullQualifiedClassName = $this->getNamespaceOfScope($phpcsFile, $currScope);
                $fullQualifiedClassName .= '\\' . $phpcsFile->getDeclarationName($currScope);
            } else {
                $declarationName = $phpcsFile->getDeclarationName($currScope);
                $fullQualifiedClassName = $tokens[$calledClassName]['content'];
            }

            if ($declarationName === $fullQualifiedClassName) {
                // Class name is the same as the current class, which is not allowed
                // except if being used inside a closure.
                if ($phpcsFile->hasCondition($stackPtr, T_CLOSURE) === false) {
                    $error = 'Must use "self::" for local static member reference';
                    $phpcsFile->addError($error, $calledClassName, 'NotUsed');

                    return;
                }
            }
        }//end if

        if ($tokens[($stackPtr - 1)]['code'] === T_WHITESPACE) {
            $found = strlen($tokens[($stackPtr - 1)]['content']);
            $error = 'Expected 0 spaces before double colon; %s found';
            $data = [$found];
            $phpcsFile->addError($error, $calledClassName, 'SpaceBefore', $data);
        }

        if ($tokens[($stackPtr + 1)]['code'] === T_WHITESPACE) {
            $found = strlen($tokens[($stackPtr + 1)]['content']);
            $error = 'Expected 0 spaces after double colon; %s found';
            $data = [$found];
            $phpcsFile->addError($error, $calledClassName, 'SpaceAfter', $data);
        }
    }//end processTokenWithinScope()

    /**
     * Returns the declaration names for classes/interfaces/functions with a namespace.
     *
     * @param array $tokens   Token stack for this file.
     * @param int   $stackPtr The position where the namespace building will start.
     *
     * @return string
     */
    protected function getDeclarationNameWithNamespace(array $tokens, $stackPtr)
    {
        $nameParts = [];
        $currentPointer = $stackPtr;
        while ($tokens[$currentPointer]['code'] === T_NS_SEPARATOR
            || $tokens[$currentPointer]['code'] === T_STRING
        ) {
            $nameParts[] = $tokens[$currentPointer]['content'];
            $currentPointer--;
        }

        $nameParts = array_reverse($nameParts);

        return implode('', $nameParts);
    }//end getDeclarationNameWithNamespace()

    /**
     * Returns the namespace declaration of a file.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file where this token was found.
     * @param int                  $stackPtr  The position where the search for the
     *                                        namespace declaration will start.
     *
     * @return string
     */
    protected function getNamespaceOfScope(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $namespace = '\\';
        $namespaceDeclaration = $phpcsFile->findPrevious(T_NAMESPACE, $stackPtr);

        if ($namespaceDeclaration !== false) {
            $endOfNamespaceDeclaration = $phpcsFile->findNext(T_SEMICOLON, $namespaceDeclaration);
            $namespace = $this->getDeclarationNameWithNamespace(
                $phpcsFile->getTokens(),
                ($endOfNamespaceDeclaration - 1)
            );
        }

        return $namespace;
    }//end getNamespaceOfScope
}
