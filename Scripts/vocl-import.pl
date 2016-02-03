#!/usr/bin/perl
#
# Processes files sent to vocl@organicdesign.co.nz and enters the information into the site
#
use File::Basename;
use Cwd qw( realpath );
use Time::HiRes;
use Digest::MD5 qw( md5_base64 );

# Die if unoconv not found
die "This script depends on unoconv!" unless qx( which unoconv );

# Die if sender not valid
#$sender = $ENV{SENDER};

# Make a tmp dir to work in
#$tmpDir = md5_base64( Time::HiRes::time . rand() );
#$tmpDir =~ s/\W//g;
#$tmpDir = "/tmp/vocl-" . substr( $tmpDir, 1, 5 );
#mkdir $tmpDir;
$tmpDir = '/tmp';
$file = 'vocl.docx';

# Extract the attachments and copy them to the tmp dir
#$msg = $ARGV[0];

# Find the Word doc, bail if none, or more than one

# Convert the Word doc into HTML and get content
qx( unoconv -f html "$tmpDir/$file.docx" );
open FH, '<', $file or bail( "Couldn't read \"$file.html\"!" );
sysread FH, $html, -s "$tmpDir/$file.html";
close FH;

# Upload the images

# Preprocess HTML

	# Remove all spans
	$html =~ s/<\/?span.*?>//g;

	# Remove all styles in p elements
	$html =~ s/<p style.+?>/<p>/g;

	# Normalise LINK content to just the [LINK:url] (some urls are emails)
	$html =~ s/\[LINK:.*?(http.+)("|<).*?]/[LINK:$1]/g;
	$html =~ s/\[LINK:.*?([-_.0-9a-z]+@[-_0-9a-z]+).*?\]/[LINK:mailto:$1]/g;

	# Remove font tags surrounding LINK
	$html =~ s/<font.+?>\s*(\[LINK:.+?\].*?)<\/font>/$1/g;

	# Remove all font tags with colour 0
	# Remove <em>
	# Change <strong...> to <b>
	# Remove <a name>
	# Change <p><br></p> to just br (?)


	# Get volumne, issue etc prior to first ****

open FH,'>', "$tmpDir/$file.2.html" or die bail( "Couldn't write processed html" );
print FH $html;
close FH;





# Remove tmp dir and die
sub bail {
	my $msg = shift;
	#qx( rm -rf $tmpDir ) if -d $tmpDir;
	die $msg if $msg;
	exit;
}
