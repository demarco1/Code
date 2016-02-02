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
$sender = $ENV{SENDER};

# Make a tmp dir to work in
$tmpDir = md5_base64( Time::HiRes::time . rand() );
$tmpDir =~ s/\W//g;
$tmpDir = "/tmp/vocl-" . substr( $tmpDir, 1, 5 );
mkdir $tmpDir;

# Extract the attachments and copy them to the tmp dir
$msg = $ARGV[0];

# Find the Word doc, bail if none, or more than one

# Convert the Word doc into HTML
qx( unoconv -f html "$tmpDir/$file.docx" );

# Upload the images

# Parse the HTML
open HTML, '<', "$tmpDir/$file.html" or bail( "Couldn't open \"$tmpDir/$file.html\" for reading!" );
while(<HTML>) {




}





# Remove tmp dir and die
sub bail {
	my $msg = shift;
	qx( rm -rf $tmpDir ) if -d $tmpDir;
	die $msg if $msg;
	exit;
}
