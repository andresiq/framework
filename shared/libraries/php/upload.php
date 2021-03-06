<?php

require_once ($_SERVER['DOCUMENT_ROOT']."/settings/Start.php");


// $response armazena a resposta no formato padrão da orbtalAPI
// sempre enviada à camada cliente em JSON UTF-8
$response = array();

$mime_type = array(
 "applicaiton/x-bytecode.python" => ".pyc"
,"application/acad" => ".dwg"
,"application/arj" => ".arj"
,"application/base64" => ".mm"
,"application/base64" => ".mme"
,"application/binhex" => ".hqx"
,"application/binhex4" => ".hqx"
,"application/book" => ".boo"
,"application/book" => ".book"
,"application/cdf" => ".cdf"
,"application/clariscad" => ".ccad"
,"application/commonground" => ".dp"
,"application/drafting" => ".drw"
,"application/dsptype" => ".tsp"
,"application/dxf" => ".dxf"
,"application/envoy" => ".evy"
,"application/excel" => ".xl"
,"application/excel" => ".xla"
,"application/excel" => ".xlb"
,"application/excel" => ".xlc"
,"application/excel" => ".xld"
,"application/excel" => ".xlk"
,"application/excel" => ".xll"
,"application/excel" => ".xlm"
,"application/excel" => ".xls"
,"application/excel" => ".xlt"
,"application/excel" => ".xlv"
,"application/excel" => ".xlw"
,"application/fractals" => ".fif"
,"application/freeloader" => ".frl"
,"application/futuresplash" => ".spl"
,"application/gnutar" => ".tgz"
,"application/groupwise" => ".vew"
,"application/hlp" => ".hlp"
,"application/hta" => ".hta"
,"application/i-deas" => ".unv"
,"application/iges" => ".iges"
,"application/iges" => ".igs"
,"application/inf" => ".inf"
,"application/java-byte-code" => ".class"
,"application/java" => ".class"
,"application/lha" => ".lha"
,"application/lzx" => ".lzx"
,"application/mac-binary" => ".bin"
,"application/mac-binhex" => ".hqx"
,"application/mac-binhex40" => ".hqx"
,"application/mac-compactpro" => ".cpt"
,"application/macbinary" => ".bin"
,"application/marc" => ".mrc"
,"application/mbedlet" => ".mbd"
,"application/mcad" => ".mcd"
,"application/mime" => ".aps"
,"application/mspowerpoint" => ".pot"
,"application/mspowerpoint" => ".pps"
,"application/mspowerpoint" => ".ppt"
,"application/mspowerpoint" => ".ppz"
,"application/msword" => ".doc"
,"application/msword" => ".dot"
,"application/msword" => ".w6w"
,"application/msword" => ".wiz"
,"application/msword" => ".word"
,"application/mswrite" => ".wri"
,"application/netmc" => ".mcp"
,"application/octet-stream" => ".a"
,"application/octet-stream" => ".arc"
,"application/octet-stream" => ".arj"
,"application/octet-stream" => ".bin"
,"application/octet-stream" => ".com"
,"application/octet-stream" => ".dump"
,"application/octet-stream" => ".exe"
,"application/octet-stream" => ".lha"
,"application/octet-stream" => ".lhx"
,"application/octet-stream" => ".lzh"
,"application/octet-stream" => ".lzx"
,"application/octet-stream" => ".o"
,"application/octet-stream" => ".psd"
,"application/octet-stream" => ".saveme"
,"application/octet-stream" => ".uu"
,"application/octet-stream" => ".zoo"
,"application/oda" => ".oda"
,"application/pdf" => ".pdf"
,"application/pkcs-12" => ".p12"
,"application/pkcs-crl" => ".crl"
,"application/pkcs10" => ".p10"
,"application/pkcs7-mime" => ".p7c"
,"application/pkcs7-mime" => ".p7m"
,"application/pkcs7-signature" => ".p7s"
,"application/pkix-cert" => ".cer"
,"application/pkix-cert" => ".crt"
,"application/pkix-crl" => ".crl"
,"application/plain" => ".text"
,"application/postscript" => ".ai"
,"application/postscript" => ".eps"
,"application/postscript" => ".ps"
,"application/powerpoint" => ".ppt"
,"application/pro_eng" => ".part"
,"application/pro_eng" => ".prt"
,"application/ringing-tones" => ".rng"
,"application/rtf" => ".rtf"
,"application/rtf" => ".rtx"
,"application/sdp" => ".sdp"
,"application/sea" => ".sea"
,"application/set" => ".set"
,"application/sla" => ".stl"
,"application/smil" => ".smi"
,"application/smil" => ".smil"
,"application/solids" => ".sol"
,"application/sounder" => ".sdr"
,"application/step" => ".step"
,"application/step" => ".stp"
,"application/streamingmedia" => ".ssm"
,"application/toolbook" => ".tbk"
,"application/vda" => ".vda"
,"application/vnd.fdf" => ".fdf"
,"application/vnd.hp-hpgl" => ".hgl"
,"application/vnd.hp-hpgl" => ".hpg"
,"application/vnd.hp-hpgl" => ".hpgl"
,"application/vnd.hp-pcl" => ".pcl"
,"application/vnd.ms-excel" => ".xlb"
,"application/vnd.ms-excel" => ".xlc"
,"application/vnd.ms-excel" => ".xll"
,"application/vnd.ms-excel" => ".xlm"
,"application/vnd.ms-excel" => ".xls"
,"application/vnd.ms-excel" => ".xlw"
,"application/vnd.ms-pki.certstore" => ".sst"
,"application/vnd.ms-pki.pko" => ".pko"
,"application/vnd.ms-pki.seccat" => ".cat"
,"application/vnd.ms-pki.stl" => ".stl"
,"application/vnd.ms-powerpoint" => ".pot"
,"application/vnd.ms-powerpoint" => ".ppa"
,"application/vnd.ms-powerpoint" => ".pps"
,"application/vnd.ms-powerpoint" => ".ppt"
,"application/vnd.ms-powerpoint" => ".pwz"
,"application/vnd.ms-project" => ".mpp"
,"application/vnd.nokia.configuration-message" => ".ncm"
,"application/vnd.nokia.ringing-tone" => ".rng"
,"application/vnd.rn-realmedia" => ".rm"
,"application/vnd.rn-realplayer" => ".rnx"
,"application/vnd.wap.wmlc" => ".wmlc"
,"application/vnd.wap.wmlscriptc" => ".wmlsc"
,"application/vnd.xara" => ".web"
,"application/vocaltec-media-desc" => ".vmd"
,"application/vocaltec-media-file" => ".vmf"
,"application/wordperfect" => ".wp"
,"application/wordperfect" => ".wp5"
,"application/wordperfect" => ".wp6"
,"application/wordperfect" => ".wpd"
,"application/wordperfect6.0" => ".w60"
,"application/wordperfect6.0" => ".wp5"
,"application/wordperfect6.1" => ".w61"
,"application/x-123" => ".wk1"
,"application/x-aim" => ".aim"
,"application/x-authorware-bin" => ".aab"
,"application/x-authorware-map" => ".aam"
,"application/x-authorware-seg" => ".aas"
,"application/x-bcpio" => ".bcpio"
,"application/x-binary" => ".bin"
,"application/x-binhex40" => ".hqx"
,"application/x-bsh" => ".bsh"
,"application/x-bsh" => ".sh"
,"application/x-bsh" => ".shar"
,"application/x-bytecode.elisp" => ".elc"
,"application/x-bzip" => ".bz"
,"application/x-bzip2" => ".boz"
,"application/x-bzip2" => ".bz2"
,"application/x-cdf" => ".cdf"
,"application/x-cdlink" => ".vcd"
,"application/x-chat" => ".cha"
,"application/x-chat" => ".chat"
,"application/x-cmu-raster" => ".ras"
,"application/x-cocoa" => ".cco"
,"application/x-compactpro" => ".cpt"
,"application/x-compress" => ".z"
,"application/x-compressed" => ".gz"
,"application/x-compressed" => ".tgz"
,"application/x-compressed" => ".z"
,"application/x-compressed" => ".zip"
,"application/x-conference" => ".nsc"
,"application/x-cpio" => ".cpio"
,"application/x-cpt" => ".cpt"
,"application/x-csh" => ".csh"
,"application/x-deepv" => ".deepv"
,"application/x-director" => ".dcr"
,"application/x-director" => ".dir"
,"application/x-director" => ".dxr"
,"application/x-dvi" => ".dvi"
,"application/x-elc" => ".elc"
,"application/x-envoy" => ".env"
,"application/x-envoy" => ".evy"
,"application/x-esrehber" => ".es"
,"application/x-excel" => ".xla"
,"application/x-excel" => ".xlb"
,"application/x-excel" => ".xlc"
,"application/x-excel" => ".xld"
,"application/x-excel" => ".xlk"
,"application/x-excel" => ".xll"
,"application/x-excel" => ".xlm"
,"application/x-excel" => ".xls"
,"application/x-excel" => ".xlt"
,"application/x-excel" => ".xlv"
,"application/x-excel" => ".xlw"
,"application/x-frame" => ".mif"
,"application/x-freelance" => ".pre"
,"application/x-gsp" => ".gsp"
,"application/x-gss" => ".gss"
,"application/x-gtar" => ".gtar"
,"application/x-gzip" => ".gz"
,"application/x-gzip" => ".gzip"
,"application/x-hdf" => ".hdf"
,"application/x-helpfile" => ".help"
,"application/x-helpfile" => ".hlp"
,"application/x-httpd-imap" => ".imap"
,"application/x-ima" => ".ima"
,"application/x-internett-signup" => ".ins"
,"application/x-inventor" => ".iv"
,"application/x-ip2" => ".ip"
,"application/x-java-class" => ".class"
,"application/x-java-commerce" => ".jcm"
,"application/x-javascript" => ".js"
,"application/x-koan" => ".skd"
,"application/x-koan" => ".skm"
,"application/x-koan" => ".skp"
,"application/x-koan" => ".skt"
,"application/x-ksh" => ".ksh"
,"application/x-latex" => ".latex"
,"application/x-latex" => ".ltx"
,"application/x-lha" => ".lha"
,"application/x-lisp" => ".lsp"
,"application/x-livescreen" => ".ivy"
,"application/x-lotus" => ".wq1"
,"application/x-lotusscreencam" => ".scm"
,"application/x-lzh" => ".lzh"
,"application/x-lzx" => ".lzx"
,"application/x-mac-binhex40" => ".hqx"
,"application/x-macbinary" => ".bin"
,"application/x-magic-cap-package-1.0" => ".mc$"
,"application/x-mathcad" => ".mcd"
,"application/x-meme" => ".mm"
,"application/x-midi" => ".mid"
,"application/x-midi" => ".midi"
,"application/x-mif" => ".mif"
,"application/x-mix-transfer" => ".nix"
,"application/x-mplayer2" => ".asx"
,"application/x-msexcel" => ".xla"
,"application/x-msexcel" => ".xls"
,"application/x-msexcel" => ".xlw"
,"application/x-mspowerpoint" => ".ppt"
,"application/x-navi-animation" => ".ani"
,"application/x-navidoc" => ".nvd"
,"application/x-navimap" => ".map"
,"application/x-navistyle" => ".stl"
,"application/x-netcdf" => ".cdf"
,"application/x-netcdf" => ".nc"
,"application/x-newton-compatible-pkg" => ".pkg"
,"application/x-nokia-9000-communicator-add-on-software" => ".aos"
,"application/x-omc" => ".omc"
,"application/x-omcdatamaker" => ".omcd"
,"application/x-omcregerator" => ".omcr"
,"application/x-pagemaker" => ".pm4"
,"application/x-pagemaker" => ".pm5"
,"application/x-pcl" => ".pcl"
,"application/x-pixclscript" => ".plx"
,"application/x-pkcs10" => ".p10"
,"application/x-pkcs12" => ".p12"
,"application/x-pkcs7-certificates" => ".spc"
,"application/x-pkcs7-certreqresp" => ".p7r"
,"application/x-pkcs7-mime" => ".p7c"
,"application/x-pkcs7-mime" => ".p7m"
,"application/x-pkcs7-signature" => ".p7a"
,"application/x-pointplus" => ".css"
,"application/x-portable-anymap" => ".pnm"
,"application/x-project" => ".mpc"
,"application/x-project" => ".mpt"
,"application/x-project" => ".mpv"
,"application/x-project" => ".mpx"
,"application/x-qpro" => ".wb1"
,"application/x-rtf" => ".rtf"
,"application/x-sdp" => ".sdp"
,"application/x-sea" => ".sea"
,"application/x-seelogo" => ".sl"
,"application/x-sh" => ".sh"
,"application/x-shar" => ".sh"
,"application/x-shar" => ".shar"
,"application/x-shockwave-flash" => ".swf"
,"application/x-sit" => ".sit"
,"application/x-sprite" => ".spr"
,"application/x-sprite" => ".sprite"
,"application/x-stuffit" => ".sit"
,"application/x-sv4cpio" => ".sv4cpio"
,"application/x-sv4crc" => ".sv4crc"
,"application/x-tar" => ".tar"
,"application/x-tbook" => ".sbk"
,"application/x-tbook" => ".tbk"
,"application/x-tcl" => ".tcl"
,"application/x-tex" => ".tex"
,"application/x-texinfo" => ".texi"
,"application/x-texinfo" => ".texinfo"
,"application/x-troff-man" => ".man"
,"application/x-troff-me" => ".me"
,"application/x-troff-ms" => ".ms"
,"application/x-troff-msvideo" => ".avi"
,"application/x-troff" => ".roff"
,"application/x-troff" => ".t"
,"application/x-troff" => ".tr"
,"application/x-ustar" => ".ustar"
,"application/x-visio" => ".vsd"
,"application/x-visio" => ".vst"
,"application/x-visio" => ".vsw"
,"application/x-vnd.audioexplosion.mzz" => ".mzz"
,"application/x-vnd.ls-xpix" => ".xpix"
,"application/x-vrml" => ".vrml"
,"application/x-wais-source" => ".src"
,"application/x-wais-source" => ".wsrc"
,"application/x-winhelp" => ".hlp"
,"application/x-wintalk" => ".wtk"
,"application/x-world" => ".svr"
,"application/x-world" => ".wrl"
,"application/x-wpwin" => ".wpd"
,"application/x-wri" => ".wri"
,"application/x-x509-ca-cert" => ".cer"
,"application/x-x509-ca-cert" => ".crt"
,"application/x-x509-ca-cert" => ".der"
,"application/x-x509-user-cert" => ".crt"
,"application/x-zip-compressed" => ".zip"
,"application/xml" => ".xml"
,"application/zip" => ".zip"
,"audio/aiff" => ".aif"
,"audio/aiff" => ".aifc"
,"audio/aiff" => ".aiff"
,"audio/basic" => ".au"
,"audio/basic" => ".snd"
,"audio/it" => ".it"
,"audio/make.my.funk" => ".pfunk"
,"audio/make" => ".funk"
,"audio/make" => ".my"
,"audio/make" => ".pfunk"
,"audio/mid" => ".rmi"
,"audio/midi" => ".kar"
,"audio/midi" => ".mid"
,"audio/midi" => ".midi"
,"audio/mod" => ".mod"
,"audio/mpeg" => ".m2a"
,"audio/mpeg" => ".mp2"
,"audio/mpeg" => ".mpa"
,"audio/mpeg" => ".mpg"
,"audio/mpeg" => ".mpga"
,"audio/mpeg3" => ".mp3"
,"audio/nspaudio" => ".la"
,"audio/nspaudio" => ".lma"
,"audio/s3m" => ".s3m"
,"audio/tsp-audio" => ".tsi"
,"audio/tsplayer" => ".tsp"
,"audio/vnd.qcelp" => ".qcp"
,"audio/voc" => ".voc"
,"audio/voxware" => ".vox"
,"audio/wav" => ".wav"
,"audio/x-adpcm" => ".snd"
,"audio/x-aiff" => ".aif"
,"audio/x-aiff" => ".aifc"
,"audio/x-aiff" => ".aiff"
,"audio/x-au" => ".au"
,"audio/x-gsm" => ".gsd"
,"audio/x-gsm" => ".gsm"
,"audio/x-jam" => ".jam"
,"audio/x-liveaudio" => ".lam"
,"audio/x-mid" => ".mid"
,"audio/x-mid" => ".midi"
,"audio/x-midi" => ".mid"
,"audio/x-midi" => ".midi"
,"audio/x-mod" => ".mod"
,"audio/x-mpeg-3" => ".mp3"
,"audio/x-mpeg" => ".mp2"
,"audio/x-mpequrl" => ".m3u"
,"audio/x-nspaudio" => ".la"
,"audio/x-nspaudio" => ".lma"
,"audio/x-pn-realaudio-plugin" => ".ra"
,"audio/x-pn-realaudio-plugin" => ".rmp"
,"audio/x-pn-realaudio-plugin" => ".rpm"
,"audio/x-pn-realaudio" => ".ra"
,"audio/x-pn-realaudio" => ".ram"
,"audio/x-pn-realaudio" => ".rm"
,"audio/x-pn-realaudio" => ".rmm"
,"audio/x-pn-realaudio" => ".rmp"
,"audio/x-psid" => ".sid"
,"audio/x-realaudio" => ".ra"
,"audio/x-twinvq-plugin" => ".vqe"
,"audio/x-twinvq-plugin" => ".vql"
,"audio/x-twinvq" => ".vqf"
,"audio/x-vnd.audioexplosion.mjuicemediafile" => ".mjf"
,"audio/x-voc" => ".voc"
,"audio/x-wav" => ".wav"
,"audio/xm" => ".xm"
,"chemical/x-pdb" => ".pdb"
,"chemical/x-pdb" => ".xyz"
,"drawing/x-dwf" => ".dwf"
,"flv-application/octet-stream" => ".flv"
,"i-world/i-vrml" => ".ivr"
,"image/bmp" => ".bm"
,"image/bmp" => ".bmp"
,"image/cmu-raster" => ".ras"
,"image/cmu-raster" => ".rast"
,"image/fif" => ".fif"
,"image/florian" => ".flo"
,"image/florian" => ".turbot"
,"image/g3fax" => ".g3"
,"image/gif" => ".gif"
,"image/ief" => ".ief"
,"image/ief" => ".iefs"
,"image/jpeg" => ".jfif"
,"image/jpeg" => ".jfif-tbnl"
,"image/jpeg" => ".jpe"
,"image/jpeg" => ".jpeg"
,"image/jpeg" => ".jpg"
,"image/jutvision" => ".jut"
,"image/naplps" => ".nap"
,"image/naplps" => ".naplps"
,"image/pict" => ".pic"
,"image/pict" => ".pict"
,"image/pjpeg" => ".jfif"
,"image/pjpeg" => ".jpe"
,"image/pjpeg" => ".jpeg"
,"image/pjpeg" => ".jpg"
,"image/png" => ".png"
,"image/tiff" => ".tif"
,"image/tiff" => ".tiff"
,"image/vasa" => ".mcf"
,"image/vnd.dwg" => ".dwg"
,"image/vnd.dwg" => ".dxf"
,"image/vnd.dwg" => ".svf"
,"image/vnd.fpx" => ".fpx"
,"image/vnd.net-fpx" => ".fpx"
,"image/vnd.rn-realflash" => ".rf"
,"image/vnd.rn-realpix" => ".rp"
,"image/vnd.wap.wbmp" => ".wbmp"
,"image/vnd.xiff" => ".xif"
,"image/x-cmu-raster" => ".ras"
,"image/x-dwg" => ".dwg"
,"image/x-dwg" => ".dxf"
,"image/x-dwg" => ".svf"
,"image/x-icon" => ".ico"
,"image/x-jg" => ".art"
,"image/x-jps" => ".jps"
,"image/x-niff" => ".nif"
,"image/x-niff" => ".niff"
,"image/x-pcx" => ".pcx"
,"image/x-pict" => ".pct"
,"image/x-portable-anymap" => ".pnm"
,"image/x-portable-bitmap" => ".pbm"
,"image/x-portable-graymap" => ".pgm"
,"image/x-portable-greymap" => ".pgm"
,"image/x-portable-pixmap" => ".ppm"
,"image/x-quicktime" => ".qif"
,"image/x-quicktime" => ".qti"
,"image/x-quicktime" => ".qtif"
,"image/x-rgb" => ".rgb"
,"image/x-tiff" => ".tif"
,"image/x-tiff" => ".tiff"
,"image/x-windows-bmp" => ".bmp"
,"image/x-xbitmap" => ".xbm"
,"image/x-xbm" => ".xbm"
,"image/x-xpixmap" => ".pm"
,"image/x-xpixmap" => ".xpm"
,"image/x-xwd" => ".xwd"
,"image/x-xwindowdump" => ".xwd"
,"image/xbm" => ".xbm"
,"image/xpm" => ".xpm"
,"message/rfc822" => ".mht"
,"message/rfc822" => ".mhtml"
,"message/rfc822" => ".mime"
,"model/iges" => ".iges"
,"model/iges" => ".igs"
,"model/vnd.dwf" => ".dwf"
,"model/vrml" => ".vrml"
,"model/vrml" => ".wrl"
,"model/vrml" => ".wrz"
,"model/x-pov" => ".pov"
,"multipart/x-gzip" => ".gzip"
,"multipart/x-ustar" => ".ustar"
,"multipart/x-zip" => ".zip"
,"music/crescendo" => ".mid"
,"music/crescendo" => ".midi"
,"music/x-karaoke" => ".kar"
,"paleovu/x-pv" => ".pvu"
,"text/asp" => ".asp"
,"text/css" => ".css"
,"text/html" => ".acgi"
,"text/html" => ".htm"
,"text/html" => ".html"
,"text/html" => ".htmls"
,"text/html" => ".htx"
,"text/html" => ".shtml"
,"text/mcf" => ".mcf"
,"text/pascal" => ".pas"
,"text/plain" => ".c"
,"text/plain" => ".c++"
,"text/plain" => ".cc"
,"text/plain" => ".com"
,"text/plain" => ".conf"
,"text/plain" => ".cxx"
,"text/plain" => ".def"
,"text/plain" => ".f"
,"text/plain" => ".f90"
,"text/plain" => ".for"
,"text/plain" => ".g"
,"text/plain" => ".h"
,"text/plain" => ".hh"
,"text/plain" => ".idc"
,"text/plain" => ".jav"
,"text/plain" => ".java"
,"text/plain" => ".list"
,"text/plain" => ".log"
,"text/plain" => ".lst"
,"text/plain" => ".m"
,"text/plain" => ".mar"
,"text/plain" => ".pl"
,"text/plain" => ".sdml"
,"text/plain" => ".text"
,"text/plain" => ".txt"
,"text/richtext" => ".rt"
,"text/richtext" => ".rtf"
,"text/richtext" => ".rtx"
,"text/scriplet" => ".wsc"
,"text/sgml" => ".sgm"
,"text/sgml" => ".sgml"
,"text/tab-separated-values" => ".tsv"
,"text/uri-list" => ".uni"
,"text/uri-list" => ".unis"
,"text/uri-list" => ".uri"
,"text/uri-list" => ".uris"
,"text/vnd.abc" => ".abc"
,"text/vnd.fmi.flexstor" => ".flx"
,"text/vnd.rn-realtext" => ".rt"
,"text/vnd.wap.wml" => ".wml"
,"text/vnd.wap.wmlscript" => ".wmls"
,"text/webviewhtml" => ".htt"
,"text/x-asm" => ".asm"
,"text/x-asm" => ".s"
,"text/x-audiosoft-intra" => ".aip"
,"text/x-c" => ".c"
,"text/x-c" => ".cc"
,"text/x-c" => ".cpp"
,"text/x-component" => ".htc"
,"text/x-fortran" => ".f"
,"text/x-fortran" => ".f77"
,"text/x-fortran" => ".f90"
,"text/x-fortran" => ".for"
,"text/x-h" => ".h"
,"text/x-h" => ".hh"
,"text/x-java-source" => ".jav"
,"text/x-java-source" => ".java"
,"text/x-la-asf" => ".lsx"
,"text/x-m" => ".m"
,"text/x-pascal" => ".p"
,"text/x-script.csh" => ".csh"
,"text/x-script.elisp" => ".el"
,"text/x-script.guile" => ".scm"
,"text/x-script.ksh" => ".ksh"
,"text/x-script.lisp" => ".lsp"
,"text/x-script.perl-module" => ".pm"
,"text/x-script.perl" => ".pl"
,"text/x-script.phyton" => ".py"
,"text/x-script.rexx" => ".rexx"
,"text/x-script.scheme" => ".scm"
,"text/x-script.sh" => ".sh"
,"text/x-script.tcl" => ".tcl"
,"text/x-script.tcsh" => ".tcsh"
,"text/x-script.zsh" => ".zsh"
,"text/x-script" => ".hlb"
,"text/x-server-parsed-html" => ".shtml"
,"text/x-server-parsed-html" => ".ssi"
,"text/x-setext" => ".etx"
,"text/x-sgml" => ".sgm"
,"text/x-sgml" => ".sgml"
,"text/x-speech" => ".spc"
,"text/x-speech" => ".talk"
,"text/x-uil" => ".uil"
,"text/x-uuencode" => ".uu"
,"text/x-uuencode" => ".uue"
,"text/x-vcalendar" => ".vcs"
,"text/xml" => ".xml"
,"video/animaflex" => ".afl"
,"video/avi" => ".avi"
,"video/avs-video" => ".avs"
,"video/dl" => ".dl"
,"video/fli" => ".fli"
,"video/gl" => ".gl"
,"video/mpeg" => ".m1v"
,"video/mpeg" => ".m2v"
,"video/mpeg" => ".mp2"
,"video/mpeg" => ".mp3"
,"video/mpeg" => ".mpa"
,"video/mpeg" => ".mpe"
,"video/mpeg" => ".mpeg"
,"video/mpeg" => ".mpg"
,"video/msvideo" => ".avi"
,"video/quicktime" => ".moov"
,"video/quicktime" => ".mov"
,"video/quicktime" => ".qt"
,"video/vdo" => ".vdo"
,"video/vivo" => ".viv"
,"video/vivo" => ".vivo"
,"video/vnd.rn-realvideo" => ".rv"
,"video/vnd.vivo" => ".viv"
,"video/vnd.vivo" => ".vivo"
,"video/vosaic" => ".vos"
,"video/x-amt-demorun" => ".xdr"
,"video/x-amt-showrun" => ".xsr"
,"video/x-atomic3d-feature" => ".fmf"
,"video/x-dl" => ".dl"
,"video/x-dv" => ".dif"
,"video/x-dv" => ".dv"
,"video/x-fli" => ".fli"
,"video/x-gl" => ".gl"
,"video/x-isvideo" => ".isu"
,"video/x-motion-jpeg" => ".mjpg"
,"video/x-mpeg" => ".mp2"
,"video/x-mpeg" => ".mp3"
,"video/x-mpeq2a" => ".mp2"
,"video/x-ms-asf-plugin" => ".asx"
,"video/x-ms-asf" => ".asf"
,"video/x-ms-asf" => ".asx"
,"video/x-msvideo" => ".avi"
,"video/x-qtc" => ".qtc"
,"video/x-scm" => ".scm"
,"video/x-sgi-movie" => ".movie"
,"video/x-sgi-movie" => ".mv"
,"windows/metafile" => ".wmf"
,"www/mime" => ".mime"
,"x-conference/x-cooltalk" => ".ice"
,"x-music/x-midi" => ".mid"
,"x-music/x-midi" => ".midi"
,"x-world/x-3dmf" => ".3dm"
,"x-world/x-3dmf" => ".3dmf"
,"x-world/x-3dmf" => ".qd3"
,"x-world/x-3dmf" => ".qd3d"
,"x-world/x-svr" => ".svr"
,"x-world/x-vrml" => ".vrml"
,"x-world/x-vrml" => ".wrl"
,"x-world/x-vrml" => ".wrz"
,"x-world/x-vrt" => ".vrt"
,"xgl/drawing" => ".xgz"
,"xgl/movie" => ".xmz"
);



function uploadFile() {
	
//	$response["success"] = false;
	$response["applicationControl"]["authenticated"] = true;
	$response["applicationControl"]["authorized"] = true;

	$dirName     = $_SERVER['DOCUMENT_ROOT']."/users/public/upload";	// path to the upload folder
	$dbdirName   = "/users/public/upload";								// relative path, to store in the db
	$maxFileSize = 10485760;												// max file size (2MB in bytes) // 2097152
	$maxWidthImageSize = 1280;											// max width in pixels to the image
	$maxWidthThumbSize = 50;                    						// max width in pixels to the image thumbnail
	
	$checkFile = isset($_GET['checkFile']) ? $_GET['checkFile'] : false;
	
	// the 'checkFile' flag verifies if the function is being called to start the upload process, 
	// or if the function is being called to check the current status of the upload proccess
	
	
	// MODE: calling for the file upload
	if (!$checkFile && isset($_FILES['sValue'])) {
		
		// check if the file is a image
		$isImageFile = eregi("^image\/(pjpeg|jpeg|jpg|png|bmp|gif)$", $_FILES['sValue']['type']);
		
		
		// try to create the upload directory, if non existent
		// REMOVE IF IT CAUSES TROUBLE IN WINDOWS ENVIRONMENT... THE UPLOAD PATH NEEDS TO EXIST!
		@mkdir($dirName, 0777, true);
		
		
		// creates a unique file name for the uploaded file
		// first, retrieves the file extension (the ".something" part)
		// then, generates a unique name using timestamp, and adds the extension
		// preg_match("/\.(pjpeg|jpeg|jpg|png|bmp|gif){1}$/i", $_FILES['sValue']['name'], $extension);
		// preg_match("/\.([0-9a-z]+){1}$/i", $_FILES['sValue']['name'], $extension);
		
		
		
		$fileMimeType = getimagesize( $_FILES['sValue']['tmp_name'] ); 
		$fileExtension = isset( $fileMimeType{"mime"} ) && isset( $mime_type{ $fileMimeType{"mime"} } ) ? $mime_type{ $fileMimeType{"mime"} } : preg_replace( "/.+(\..+)$/", "$1" , $_FILES['sValue']['name'] );
		
		$_SESSION['fileSize'] = $_FILES['sValue']['size'];
		$_SESSION['originalFileName'] = $_FILES['sValue']['name'];
		$_SESSION['sMimeType'] = $fileMimeType{"mime"};
		
		$unique    = md5(uniqid(time()));
		$fileName  = $unique.$fileExtension;
		$thumbName = $unique."-thumb".$fileExtension;
		
		
		
		// sets in the SESSION the name and the initial status of the upload process
		$_SESSION['fileUploaded'] = false;                     // the file has been created in the directory? not yet...
		$_SESSION['fileCheck']    = $dirName.'/'.$fileName;    // the path to the real file, just for checking if it has been uploaded
		$_SESSION['fileName']     = $dbdirName.'/'.$fileName;  // the unique file name
		$_SESSION['thumbName']    = $dbdirName.'/'.$thumbName; // the unique thumb name
		
		
		// if is a image file, resizes the image, and creates the thumbnail
		if ($isImageFile) {
			
			// resizes the image
			if ($fileExtension == ".jpg" || $fileExtension == ".jpeg" ) {
				$srcImage = imagecreatefromjpeg($_FILES['sValue']['tmp_name']);
				
			} else if ($fileExtension == ".png") {
				$srcImage = imagecreatefrompng($_FILES['sValue']['tmp_name']);
				
			} else {
				$srcImage = imagecreatefromgif($_FILES['sValue']['tmp_name']);
			}
			
			$dimensions  = getimagesize($_FILES['sValue']['tmp_name']);
			$imageWidth  = $dimensions[0];
			$imageHeigth = $dimensions[1];
			
			
			// resizes IMAGE based in the specified width and (very important) keeps proportions!
			// only if provided image is bigger than the max width
			$newWidth  = min($maxWidthImageSize, $imageWidth);
			$newHeight = ($imageHeigth / $imageWidth) * $newWidth;
			$tmpImage  = imagecreatetruecolor($newWidth, $newHeight);
			
			imagecopyresampled($tmpImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $imageWidth, $imageHeigth);
			imagejpeg($tmpImage, $dirName.'/'.$fileName, 100);
			
			
			// resizes THUMB based in the specified width and (very important) keeps proportions!
			$newWidth  = $maxWidthThumbSize;
			$newHeight = ($imageHeigth / $imageWidth) * $newWidth;
			$tmpThumb  = imagecreatetruecolor($newWidth, $newHeight);
			
			imagecopyresampled($tmpThumb, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $imageWidth, $imageHeigth);
			imagejpeg($tmpThumb, $dirName.'/'.$thumbName, 100);
			
			imagedestroy($srcImage);
			imagedestroy($tmpImage);
			imagedestroy($tmpThumb);
		}
		
		// if is not a image file, just stores the file (and don't makes a thumb)
		else {
			
			$_SESSION['thumbName'] = NULL;
			move_uploaded_file($_FILES['sValue']['tmp_name'], $dirName.'/'.$fileName);
		}
		
		// sets in the SESSION the status of the upload process
		$_SESSION['fileUploaded'] = true; // the file has been created in the directory? yeap!
		
	}
	
	// MODE: checking upload status
	if ($checkFile) {
		
		if (isset($_SESSION['fileName']) && isset($_SESSION['fileUploaded']) && $_SESSION['fileUploaded'] == true && file_exists($_SESSION['fileCheck'])) {
			
			// $fileSize = filesize($_SESSION['fileCheck']) * 1024;
			// the file is too big, returns an error message, in JSON format
			if (isset($_SESSION['fileSize']) && $_SESSION['fileSize'] >= $maxFileSize) {
				
				$response["success"] = false;
				
				$response["applicationControl"]["status"] = "complete";
				$response["applicationControl"]["error"]["number"]	= 5007;
				$response["applicationControl"]["error"]["type"]	= "File too large";
				$response["applicationControl"]["error"]["message"] = "The uploaded file was too large. Please try to upload a smaller file. The maximum size is $maxFileSize bytes";
				
				$response["recordset"]["sDisplayName"] = NULL;
				$response["recordset"]["sUniqueName"]  = NULL;
				$response["recordset"]["sMimeType"]    = NULL;
				$response["recordset"]["sFilePath"]    = NULL;
				$response["recordset"]["fileSize"]     = $_SESSION['fileSize'];
				
				echo json_encode(toUTF8($response));
			
				$_SESSION['fileSize']	  		= NULL;
				$_SESSION['fileCheck']	  		= NULL;
				$_SESSION['fileName']	  		= NULL;
				$_SESSION['sMimeType']			= NULL;
				$_SESSION['thumbName'] 			= NULL;
				$_SESSION['fileUploaded'] 		= NULL;
				$_SESSION['originalFileName'] 	= NULL;
				
				exit();
			}
			
			// the file has been uploaded, returns a completion message, in JSON format
			$response["success"] = true;
			
			$response["applicationControl"]["status"] = "complete";
			
			$response["recordset"]["sDisplayName"] = $_SESSION['originalFileName'];
			$response["recordset"]["sUniqueName"]  = $_SESSION['fileName'];
			$response["recordset"]["sMimeType"]    = $_SESSION['sMimeType'];
			$response["recordset"]["sFilePath"]    = $_SESSION['fileName'];
			$response["recordset"]["fileSize"]     = $_SESSION['fileSize'];
			
			echo json_encode(toUTF8($response));
			
			$_SESSION['fileCheck']	  = NULL;
			$_SESSION['fileName']	  = NULL;
			$_SESSION['thumbName']	  = NULL;
			$_SESSION['fileUploaded'] = NULL;
			
			exit();
			
		} else {
			
			// the file has not been uploaded yet, returns a loading message, in JSON format
			unset($response['success']);
			$response["applicationControl"]["status"] = "loading";
			
			echo json_encode(toUTF8($response));
			
			exit();
		}
	}
}

uploadFile();
