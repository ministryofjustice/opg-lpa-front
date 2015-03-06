<?php
namespace Application\Model\Service\Guidance;

use Michelf\Markdown;

class Guidance
{
    const GUIDANCE_MARKDOWN_FOLDER = 'public/guidance';
    const SUPPORT_PHONE = '0300 456 0300';
    const SUPPORT_EMAIL = 'customerservices@PublicGuardian.gsi.gov.uk';

    /**
     * Generate HTML from the guidance markdown files
     * 
     * @return string The generated HTML
     */
    function generateHtmlFromMarkdown()
    {
        $navigationHtml = '<nav class="help-navigation"><div class="group"><h2>Help topics</h2><ul class="help-topics">';
    
        $sections = '';
        $lines = file(self::GUIDANCE_MARKDOWN_FOLDER . '/order.md');
        
        foreach ($lines as $line) {
            if (preg_match('/^\*\w*(.*)/', $line, $matches)) {
                $sectionTitle = trim($matches[1]);
            }
    
            if (preg_match('/^\s+\*\s*(.*\.md)/', $line, $matches)) {
                $sectionFilename = trim($matches[1]);
                $sectionId = trim(strtolower(str_replace(' ', '-', $sectionTitle)));
                $sections .= $this->processSection($sectionFilename, $sectionId);
                $url = '/help/#topic-' . $sectionId;
                $dataJourney = 'stageprompt.lpa:help:' . $sectionId;
                $navigationHtml .= '<li><a class="js-guidance" href="' . $url . '" data-journey="' . $dataJourney . '">' . $sectionTitle . '</a></li>';
            }
        }
    
        $navigationHtml .= '</ul></div></nav>';
    
        $html .= '<section id="help-system"><header><p>A guide to making your lasting power of attorney</p></header>';
        $html .= $navigationHtml;
        $html .= '<div class="content help-sections">';
        $html .= $sections; 
        $html .= '</div>';
        $html .= '<div class="action group">';
        $html .= '<p>';
        $html .= 'Need help? Ring us on <strong>' . SUPPORT_PHONE . '</strong>. ';
        $html .= 'Alternatively, email us at ';
        $html .= '<strong><a href="mailto:' . SUPPORT_EMAIL . '?subject=Digital%20LPA%20Enquiry">' . SUPPORT_EMAIL . '</a></strong>';
        $html .= '</p>';
        $html .= '<hr>';
        $html .= '<a href="#" class="js-popup-close button-secondary">Close help</a>';
        $html .= '</div>';
        $html .= '</section>';
    
        return $html;
    }
    
    function processSection($filename, $sectionId)
    {
        $md = Markdown::defaultTransform(file_get_contents(self::GUIDANCE_MARKDOWN_FOLDER . '/' . $filename));
        $retval = '';
        $retval.= PHP_EOL . '<article id="topic-' . $sectionId . '">';
        $retval.= preg_replace('/<a href="\/help\/#topic-(.+)">(.+)<\/a>/', '<a href="/help/#topic-${1}" class="js-guidance" data-journey="stageprompt.lpa:help:${1}">${2}</a>', $md);
        $retval.= PHP_EOL . '</article>';
        return $retval;
    }
}