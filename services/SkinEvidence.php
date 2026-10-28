<?php
declare(strict_types=1);

/** Only references supplied by the project owner. Evidence is not validation of this implementation. */
final class SkinEvidence
{
    public const VERSION = 'purevia-observations-2026-10-27';
    public static function sources(): array
    {
        return [
            'sos2014'=>['title'=>'A Validated Questionnaire for Quantifying Skin Oiliness (2014)', 'url'=>'https://www.scirp.org/journal/paperinformation?paperid=43949', 'scope'=>'Oiliness measurement; validation belongs to the original instrument, not these adapted questions.'],
            'bstq2025'=>['title'=>'BSTQ: validation of the Polish language version, part one (2025)', 'url'=>'https://pmc.ncbi.nlm.nih.gov/articles/PMC12163966/', 'scope'=>'Oily/dry and sensitive/resistant dimensions in the studied population; does not validate this four-type finder.'],
            'cho2023'=>['title'=>'Cho et al.: BSTQ and digital skin analysis in South Korea (2023)', 'url'=>'https://doi.org/10.1111/jocd.15820', 'scope'=>'Population-specific shortened questionnaire study; its findings do not transfer automatically to new wording or cutoffs.'],
            'baumann'=>['title'=>'Cosmetic Dermatology: Principles and Practice (supplied textbook)', 'url'=>null, 'scope'=>'Skin characteristics and ingredient background: chapters 17 (burning/stinging, p. 133), 18 (contact dermatitis, p. 136), 30 (retinoids, p. 256), 32 (moisturizers, p. 273) and 36 (fragrance, p. 325).'],
            'classification'=>['title'=>'Parraga et al.: Skin type classifications: does the perfect assessment exist? (2024)', 'url'=>'https://doi.org/10.1080/09546634.2024.2427325', 'scope'=>'Classification context and limitations; phototype is distinct from oiliness and sensitivity.'],
            'jcdsa2016'=>['title'=>'Baumann: Validation of a Questionnaire to Diagnose the Baumann Skin Type in All Ethnicities and in Various Geographic Locations (2016)', 'url'=>'https://doi.org/10.4236/jcdsa.2016.61005', 'scope'=>'Reports distributions for the original BSTI across online/office settings; not evidence of diagnostic accuracy for this custom algorithm.'],
            'pdf273377050'=>['title'=>'Diržaite: Dry and oily skin types. Overview of their main conditions and care: a theoretical synthesis (2025)', 'url'=>null, 'scope'=>'Theoretical care background, not a validation study of this questionnaire.'],
            'fitzpatrick2013'=>['title'=>'Eilers et al.: self-reported Fitzpatrick phototypes (2013), supplied supplement', 'url'=>null, 'scope'=>'Sun response/phototype only; excluded from oiliness scoring.'],
            'faceq2016'=>['title'=>'Klassen et al.: FACE-Q appearance scales (2016), supplied supplement', 'url'=>null, 'scope'=>'Appearance satisfaction and procedure outcomes; excluded from skin-type scoring.'],
            'sts'=>['title'=>'Skin Type Solutions: About us', 'url'=>'https://skintypesolutions.com/pages/about-us', 'scope'=>'Commercial description of its proprietary system; PureVia is not the official BSTQ or an STS-approved assessment.'],
        ];
    }
    public static function guidance(array $p): array
    {
        $items=[];
        $add=static function(string $title,string $text,array $sources) use (&$items): void {$items[]=compact('title','text','sources');};
        $a=$p['answers'];
        if(in_array($a['q6']??'', ['A','D'], true))$add('Possible dryness or flaking','You reported dry patches. This is a symptom to review, not a diagnosis of dehydration or eczema.',['baumann']);
        if($p['sensitivity']['frequent_reaction']||$p['sensitivity']['level']==='high') {
            $add('Reported irritation','Your answers describe product-related discomfort. Persistent, severe or recurring irritation needs a dermatologist; this quiz cannot identify an allergy or rosacea.',['baumann','bstq2025']);
            $add('Ingredients to approach cautiously','Consider fragrance-free products if fragrance has caused discomfort. Avoid products that previously caused a reaction and discuss suspected allergies with a clinician. Strong exfoliating acids and retinoids may worsen already irritated skin; this is a caution, not a blanket ban or allergy finding.',['baumann']);
        }
        if(($a['q11']??'')==='C')$add('Fragrance caution','You reported frequent discomfort with fragranced or strong formulas. Consider fragrance / parfum when reviewing labels, but this answer cannot tell which ingredient caused the reaction.',['baumann']);
        $add('Your ingredient exclusions','Only ingredients you explicitly select are excluded from matching. These observations do not prove an ingredient allergy or establish that a product is safe. Check the current full product label.',['baumann']);
        return $items;
    }
}
