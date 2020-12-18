<?php
/**
 * Class KeywordValidator
 * Find incorrectly formatted keywords and format them
 * @author Dilyan Atanasov 18/02/2020
 */

class KeywordValidator
{
    CONST regexLowercase = "/([ |,|;|-])\b([a-z].*?)\b/";
    CONST regexUppercase = "/([ |'|,|;])\b([A-Z].*?)\b/";
    CONST exceptionsUppercase = ["CPA","MCA","PC","CPR","DJ","GPS","TV","IT","3D","RV","ATV","UITV","HVAC","AC"];
    CONST states = ["AL","AK","AZ","AR","CA","CO","CT","DE","FL","GA","HI","ID","IL","IN","IA","KS","KY","LA","ME","MD","MA","MI","MN","MS","MO","MT","NE","NV","NH","NJ","NM","NY","NC","ND","OH","OK","OR","PA","RI","SC","SD","TN","TX","UT","VT","VA","WA","WV","WI","WY"];
    CONST symbols = [",",";","-"];

    private $exceptionsLowercase = ["and","in","as","at","near","by","for","from","into","like","of","off","onto","on","over","to","with","an","a"];
    private $withStates = false;

    /**
     * @param $keywordString
     * @param bool $withStates
     * @return string
     */
    public function formatKeywords($keywordString, $withStates = false){
        if(empty($keywordString)){
            return $keywordString;
        }
        // If $withStates is set to true remove overlaping kw's with states
        if($withStates){
            if (($key = array_search('in', $this->exceptionsLowercase)) !== false) {
                unset($this->exceptionsLowercase[$key]);
            }
            $this->withStates = true;
        }

        $keywordString = $this->prepareKeywordString($keywordString);
        $exceptionsFound = $this->validateKeywordString($keywordString);
        if(!empty($exceptionsFound['lowercase']['count']) || !empty($exceptionsFound['uppercase']['count'])){
            $keywordString = $this->convertKeywordString($keywordString, $exceptionsFound);
        }
        return trim($keywordString);
    }

    /**
     * @param $keywordString
     * @return string
     */
    private function prepareKeywordString($keywordString){
        $keywordString = " ".$keywordString." ";
        return $keywordString;
    }

    /**
     * @param $keywordString
     * @return array
     */
    private function validateKeywordString($keywordString){
        preg_match_all(self::regexLowercase, $keywordString, $matchesLowercase);
        preg_match_all(self::regexUppercase, $keywordString, $matchesUppercase);
        return [
            'lowercase' => [
                'count' => sizeof($matchesLowercase[0]),
                'replacementStrings' => $matchesLowercase[0],
                'searchStrings' => $matchesLowercase[2],
            ],
            'uppercase' => [
                'count' => sizeof($matchesUppercase[0]),
                'replacementStrings' => $matchesUppercase[0],
                'searchStrings' => $matchesUppercase[2],
            ]
        ];
    }

    /**
     * @param $keywordString string
     * @param $exceptionsFound array
     * @return string
     */
    private function convertKeywordString($keywordString, $exceptionsFound){
        if(!empty($exceptionsFound['lowercase']['count'])){
            $exceptions = $exceptionsFound['lowercase'];
            $keywordString = $this->exceptionsFormatter($keywordString, $exceptions);
        }
        if(!empty($exceptionsFound['uppercase']['count'])){
            $exceptions = $exceptionsFound['uppercase'];
            $keywordString = $this->exceptionsFormatter($keywordString, $exceptions);
        }
        return $keywordString;
    }

    /**
     * @param $keywordString string
     * @param $exceptions array
     * @return string
     */
    private function exceptionsFormatter($keywordString, $exceptions){
        for($i = 0; $i < sizeof($exceptions['searchStrings']); $i++){
            $incorrectlyFormattedKeywordString = $exceptions['searchStrings'][$i];
            $oldKeywordString = $exceptions['replacementStrings'][$i];
            // If the word is in the lowercase exceptions make sure it's lowercase
            if(in_array(strtolower($incorrectlyFormattedKeywordString), $this->exceptionsLowercase)){
                $exactMatch = $this->findExactMatch($keywordString, $incorrectlyFormattedKeywordString);
                $lowercaseAlternatives = $this->findLowercaseAlternatives($keywordString, $incorrectlyFormattedKeywordString);
                if(!empty($exactMatch)){
                    $keywordString = $this->formatLowercaseExceptions($exactMatch[0], $keywordString);
                }else if(!empty($lowercaseAlternatives)){
                    $alternativeLowercaseKeyword = $lowercaseAlternatives[1].$incorrectlyFormattedKeywordString;
                    $keywordString = $this->formatCapitalizeExceptions($alternativeLowercaseKeyword, $keywordString);
                }
                // If the word is in the uppercase exceptions or a state(if withStates is set to true) make sure it's uppercase
            }else if(
                in_array(strtoupper($incorrectlyFormattedKeywordString), self::exceptionsUppercase) ||
                (in_array(strtoupper($incorrectlyFormattedKeywordString), self::states) && $this->withStates)
            ){
                $keywordString = $this->formatUppercaseExceptions($oldKeywordString, $keywordString);
            }else{
                // Make lowercase all letters after ' symbol
                if(substr($oldKeywordString, 0, 1) === "'"){
                    $keywordString = $this->formatLowercaseExceptions($oldKeywordString, $keywordString);
                    continue;
                }
                $keywordString = $this->formatCapitalizeExceptions($oldKeywordString, $keywordString);
            }
        }
        return $keywordString;
    }

    /**
     * @param $keyword
     * @param $keywordString
     * @return string
     */
    private function formatLowercaseExceptions($keyword, $keywordString){
        $replacementString = strtolower($keyword);
        return str_replace($keyword, $replacementString, $keywordString);
    }

    /**
     * @param $keyword
     * @param $keywordString
     * @return string
     */
    private function formatUppercaseExceptions($keyword, $keywordString){
        $replacementString = strtoupper($keyword);
        return str_replace($keyword, $replacementString, $keywordString);
    }

    /**
     * @param $keyword
     * @param $keywordString
     * @return string
     */
    private function formatCapitalizeExceptions($keyword, $keywordString){
        $replacementString = $this->generateReplacementString($keyword);
        return str_replace($keyword, $replacementString, $keywordString);
    }

    /**
     * @param $keyword
     * @return string
     */
    private function generateReplacementString($keyword){
        $replacementString = ucwords(strtolower($keyword));
        if(in_array($keyword[0], self::symbols)){
            $replacementString = $keyword[0].ucwords(substr(strtolower($keyword), 1));
        }
        return $replacementString;
    }

    /**
     * @param $keywordString
     * @param $keyword
     * @return bool
     */
    private function findExactMatch($keywordString, $keyword){
        preg_match('/([ |,|;])\b' . $keyword . '\b([ |,|;])/', $keywordString, $result);
        return $result;
    }

    /**
     * @param $keywordString
     * @param $keyword
     * @return array
     */
    private function findLowercaseAlternatives($keywordString, $keyword){
        preg_match('/([-])\b' . $keyword . '\b([ |,|;])/', $keywordString, $result);
        return $result;
    }
}
