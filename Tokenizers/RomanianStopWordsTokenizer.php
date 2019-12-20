<?php


namespace VFou\Search\Tokenizers;

/**
 * @see https://github.com/stopwords-iso/stopwords-ro/blob/master/stopwords-ro.json
 */
class RomanianStopWordsTokenizer implements TokenizerInterface
{
    const BLACKLIST = ['a','abia','acea','aceasta','această','aceea','aceeasi','acei','aceia','acel','acela','acelasi','acele','acelea','acest','acesta','aceste','acestea','acestei','acestia','acestui','aceşti','aceştia','acolo','acord','acum','adica','ai','aia','aibă','aici','aiurea','al','ala','alaturi','ale','alea','alt','alta','altceva','altcineva','alte','altfel','alti','altii','altul','am','anume','apoi','ar','are','as','asa','asemenea','asta','astazi','astea','astfel','astăzi','asupra','atare','atat','atata','atatea','atatia','ati','atit','atita','atitea','atitia','atunci','au','avea','avem','aveţi','avut','azi','aş','aşadar','aţi','b','ba','bine','bucur','bună','c','ca','cam','cand','capat','care','careia','carora','caruia','cat','catre','caut','ce','cea','ceea','cei','ceilalti','cel','cele','celor','ceva','chiar','ci','cinci','cind','cine','cineva','cit','cita','cite','citeva','citi','citiva','conform','contra','cu','cui','cum','cumva','curând','curînd','când','cât','câte','câtva','câţi','cînd','cît','cîte','cîtva','cîţi','că','căci','cărei','căror','cărui','către','d','da','daca','dacă','dar','dat','datorită','dată','dau','de','deasupra','deci','decit','degraba','deja','deoarece','departe','desi','despre','deşi','din','dinaintea','dintr','dintr-','dintre','doar','doi','doilea','două','drept','dupa','după','dă','e','ea','ei','el','ele','era','eram','este','eu','exact','eşti','f','face','fara','fata','fel','fi','fie','fiecare','fii','fim','fiu','fiţi','foarte','fost','frumos','fără','g','geaba','graţie','h','halbă','i','ia','iar','ieri','ii','il','imi','in','inainte','inapoi','inca','incit','insa','intr','intre','isi','iti','j','k','l','la','le','li','lor','lui','lângă','lîngă','m','ma','mai','mare','mea','mei','mele','mereu','meu','mi','mie','mine','mod','mult','multa','multe','multi','multă','mulţi','mulţumesc','mâine','mîine','mă','n','ne','nevoie','ni','nici','niciodata','nicăieri','nimeni','nimeri','nimic','niste','nişte','noastre','noastră','noi','noroc','nostri','nostru','nou','noua','nouă','noştri','nu','numai','o','opt','or','ori','oricare','orice','oricine','oricum','oricând','oricât','oricînd','oricît','oriunde','p','pai','parca','patra','patru','patrulea','pe','pentru','peste','pic','pina','plus','poate','pot','prea','prima','primul','prin','printr-','putini','puţin','puţina','puţină','până','pînă','r','rog','s','sa','sa-mi','sa-ti','sai','sale','sau','se','si','sint','sintem','spate','spre','sub','sunt','suntem','sunteţi','sus','sută','sînt','sîntem','sînteţi','să','săi','său','t','ta','tale','te','ti','timp','tine','toata','toate','toată','tocmai','tot','toti','totul','totusi','totuşi','toţi','trei','treia','treilea','tu','tuturor','tăi','tău','u','ul','ului','un','una','unde','undeva','unei','uneia','unele','uneori','unii','unor','unora','unu','unui','unuia','unul','v','va','vi','voastre','voastră','voi','vom','vor','vostru','vouă','voştri','vreme','vreo','vreun','vă','x','z','zece','zero','zi','zice','îi','îl','îmi','împotriva','în','înainte','înaintea','încotro','încât','încît','între','întrucât','întrucît','îţi','ăla','ălea','ăsta','ăstea','ăştia','şapte','şase','şi','ştiu','ţi','ţie'];

    public static function tokenize($data)
    {
        return array_map(function($value){
            return !in_array($value, self::BLACKLIST) ? $value : '';
        }, $data);
    }
}
