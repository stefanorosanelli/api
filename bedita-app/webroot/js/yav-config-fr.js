/***********************************************************************
 * YAV - Yet Another Validator  v1.3.0                                 *
 * Copyright (C) 2005-2006                                             *
 * Author: Federico Crivellaro <f.crivellaro@gmail.com>                *
 * WWW: http://yav.sourceforge.net                                     *
 ***********************************************************************/

// CHANGE THESE VARIABLES FOR YOUR OWN SETUP

// if you want yav to highligh fields with errors
inputhighlight = true;
// classname you want for the error highlighting
inputclasserror = 'inputError';
// classname you want for your fields without highlighting
inputclassnormal = 'inputNormal';
// classname you want for the inner html highlighting
innererror = 'innerError';
// div name where errors will appear (or where jsVar variable is dinamically defined)
errorsdiv = 'errorsDiv';
// if you want yav to alert you for javascript errors (only for developers)
debugmode = false;
// if you want yav to trim the strings
trimenabled = true;

// change these to set your own decimal separator and your date format
DECIMAL_SEP ='.';
THOUSAND_SEP = ',';
DATE_FORMAT = 'dd-MM-yyyy';

// change these strings for your own translation (do not change {n} values!)
HEADER_MSG = 'Erreur d\'entr�e de donn�es:';
FOOTER_MSG = 'Veuillez essayer � nouveau.';
DEFAULT_MSG = 'Certaines valeurs ne sont pas valables.';
REQUIRED_MSG = 'Ce champ est requis: {1}.';
ALPHABETIC_MSG = '{1} n\'est pas une valeur valable. Caract�res permis: A-Za-z';
ALPHANUMERIC_MSG = '{1} n\'est pas une valeur valable. Caract�res permis: A-Za-z0-9';
ALNUMHYPHEN_MSG = '{1} n\'est pas une valeur valable. Caract�res permis: A-Za-z0-9\-_';
ALNUMHYPHENAT_MSG = '{1} n\'est pas une valeur valable. Caract�res permis: A-Za-z0-9\-_@';
ALPHASPACE_MSG = '{1} n\'est pas une valeur valable. Caract�res permis: A-Za-z0-9\-_espace';
MINLENGTH_MSG = '{1} doit comporter au moins {2} caract�res.';
MAXLENGTH_MSG = '{1} doit comporter au plus {2} caract�res.';
NUMRANGE_MSG = '{1} doit �tre un nombre compris dans cet intervalle: {2}.';
DATE_MSG = '{1} n\'est pas une date valable. Format requis: ' + DATE_FORMAT + '.';
NUMERIC_MSG = '{1} doit �tre un nombre.';
INTEGER_MSG = '{1} doit �tre un nombre entier.';
DOUBLE_MSG = '{1} doit �tre un nombre d�cimal.';
REGEXP_MSG = '{1} n\'est pas une valeur valable. Format requis: {2}.';
EQUAL_MSG = '{1} doit �tre �gal � {2}.';
NOTEQUAL_MSG = '{1} ne doit pas �tre �gal � {2}.';
DATE_LT_MSG = '{1} doit pr�c�der cette date: {2}.';
DATE_LE_MSG = '{1} doit pr�c�der ou �tre �gal � cette date: {2}.';
EMAIL_MSG = '{1} doit �tre une adresse email valable.';
EMPTY_MSG = '{1} doit �tre vide.';
