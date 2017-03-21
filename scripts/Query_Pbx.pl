#!/usr/bin/perl
########################################################################
##
##  Fichier.........: Query_Pbx.pl
##  Projet..........: Axa-Tech BAU
##  Createur........: Noureddine TAGHLET
##  Description.....: Script Maitre, permettant de lancer la recolte des infos
##		      sur les T2
##
##
## Structure of the file :
##
##
##  Change Log
##  ==========
##
##   ===============================================================
##    Date     |       Qui          |      Pourquoi
##   ---------------------------------------------------------------
##    20160803 | NTT                | Creation du script
##   ---------------------------------------------------------------
##
##  Copyright (c) Network Service Voice et Video
##
########################################################################

my ($block, $filename, $host, $hostname, $k_per_sec, $line,
        $num_read, $passwd, $prevblock, $prompt, $size, $size_bsd,
        $size_sysv, $start_time, $total_time, $username, $info1, $info2, $info3, $info4, $Password1, $Password, $Trunk);

my ($Database,$HostDatabase,$user,$pwd,$dbh) = ('cactidcdb','10.101.207.15','nsvvcli','IHuiGNXJLYjhXn8',undef);
my (@line)


	## Connect and login
	use strict;
	use DBI;
	use MIME::Lite; 
	use vars qw($opt_S $opt_P $opt_A  $opt_D $opt_T);
	use Getopt::Std;
	use IO::Handle;
	use Net::Telnet ();
	use DateTime;

my $usage = "usage: $0 
		\t-D Debug (default disabled)
		\t-T Type (By default PBX)
		\t-P Path of the script (/usr/share/cacti/site/scripts)
		\t-S Script 
	Please to care the option are case sensitive !!!-----";

getopts('D:T:P:S:');
#die $usage unless ($opt_I && $opt_L && $opt_P);

# Chargement des arguments
$opt_D = 0 unless $opt_D; 					# Debug
$opt_T = "Alcatel-lucent PABX" unless $opt_T; 			# Type Template
$opt_P = "/usr/share/cacti/site/scripts" unless $opt_P;		# Path
$opt_S = "Query_TrkVisu_ed1.pl" unless $opt_S;			# Script

my $Debug = $opt_D;

if ($Debug == 1) {
	print "\n";
	print "Option T : $opt_T\n";
	print "Option P : $opt_P\n";
	print "Option S : $opt_S\n\n";
	}


my $record="";
my ($data1,$data2);

my $Execution = $opt_P."/".$opt_S;

if ($Debug == 1) {print "Chemin d'execution : $Execution\n\n";}

# Connection a la BD
if (!defined($dbh = DBI->connect("DBI:mysql:database=$Database;host=$HostDatabase;port=3306",$user,$pwd))) {
        print(STDERR "Unable to connect to DB $Database ($HostDatabase)\n");
        print("U");
	exit(1);
}

# Search
my @num='';
my $ID_Template= 0;

my $sth = $dbh->prepare("select id,name from host_template ");
$sth->execute;

my $indexTemplate = "0";

while( @num =$sth->fetchrow() ) 
{
	if ($Debug == 1) {print " Template Name ($indexTemplate) : $num[0], $num[1]\n";}

	if ($opt_T eq $num[1]) { $ID_Template= $num[0];	}
	$indexTemplate++;
	
	print "\n";
}

$sth = $dbh->prepare("select hostname,notes,disabled, status, host_template_id from host where description like '%CSm'");
$sth->execute;

my $hostname_db = '';
my $Notes = '';
my $HostFound = 0;
my $HostValid = 0;
my $HostType = "";
my $Lancer = '';
my $indexCactiDB = "0";

while( @num =$sth->fetchrow() ) 
{
	$hostname_db = $num[0];
	$Notes = $num[1];

	if (($num[4] eq $ID_Template) && ($num[2] ne "on")) 
	{
	print "hostname : $hostname_db, $num[1], $num[2], $num[3]\n\n";
	$Lancer = $Execution." -L mtcl -P $hostname_db -I $hostname_db -C A -A 0 &";
	print "test : $Lancer\n\n";
	#system `$Lancer`;

	}
	
	if ($Debug == 1) {print "Contenu BDD lier a Template PABX ($indexCactiDB) : $num[0], $num[1], $num[2], $num[3], $num[4]\n";}
	$indexCactiDB++;


} # loop from database (all return value)


$sth->finish;

# Disconnect

$dbh->disconnect;

