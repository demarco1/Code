#!/usr/bin/perl
#
# Processes files sent to vocl@organicdesign.co.nz and enters the information into the site
#
use File::Basename;
use Cwd qw( realpath );
use Time::HiRes;
use Digest::MD5 qw( md5_base64 );

$dir = dirname( realpath( __FILE__ ) );

# Make a tmp dir to work in
$tmpDir = md5_base64( Time::HiRes::time . rand() );
$tmpDir =~ s/\W//g;
$tmpDir = "/tmp/vocl-" . substr( $tmpDir, 1, 5 );
mkdir $tmpDir;


# Get the zip file out of the attachment and unzip into the tmp dir

# Find the Word doc, bail if none, or more than one

# Convert the Word doc into HTML
qx( unoconv -f html "$tmpDir/$file.docx" );



# Remove tmp dir and die
sub bail {
	my $msg = shift;
	qx( rm -rf $tmpDir ) if -d $tmpDir;
	die $msg if $msg;
	exit;
}
