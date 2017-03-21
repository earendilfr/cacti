#!/usr/bin/perl -w
##
########################################################################
##
##  File.........: query_PbxStat_
##  Project......: Axa-Tech BAU
##  Creator......: Noureddine TAGHLET
##  Description..: Chargement de la structure des faisceaux pour CACTI Data Query
##
##  Lancement de la collect d information depuis le PBX
##
##
## Structure du fichier :
##
##
##  Change Log
##  ==========
##
##   ===============================================================
##    Date     |       Qui          |      Pourquoi
##   ---------------------------------------------------------------
##    20160803 | NTT                | Creation du script
##             |                    | Script modification
##   ---------------------------------------------------------------
##
##  Copyright (c) Network Service Voice et Video
##
########################################################################
##
#
#
#
	use strict;
	use DBI;
	use MIME::Lite; 
	use IO::Handle;
	use DateTime;

if (($ARGV[0] ne "query") && ($ARGV[0] ne "get") && ($ARGV[0] ne "index")) {
	print "usage:\n\n./query_unix_partitions.pl index\n./query_unix_partitions.pl query {device,mount,total,used,available,percent}\n./query_unix_partitions.pl get {device,mount,total,used,available,percent} DEVICE\n";
	exit;
}

my ($Database,$TableName,$HostDatabase,$user,$pwd,$dbh) = ('pbxdb','trunkgroupstat','10.101.207.15','nsvvcli','IHuiGNXJLYjhXn8',undef);

# Initialisation des variables de temporisations
my $DateTraitement = DateTime->now();

my $DateInfo =  $DateTraitement->date();
my $DayInfo = $DateTraitement->day();
my $MonthInfo = $DateTraitement->month();
my $YearInfo = $DateTraitement->year();

my $Heure = $DateTraitement->strftime("%H");
my $Minute = $DateTraitement->strftime("%M");
my $SecondeInfo = $DateTraitement->second();
my $WeekNumber = $DateTraitement->week_number();
my $MnRangeObserve = 0;
my $HeureRangeObserve = 0;

my $DateMesure = $DateTraitement->clone->subtract( minutes => 1 );
my $DayMesure = $DateMesure->day();
my $MonthMesure = $DateMesure->month();
my $YearMesure = $DateMesure->year();
my $HeureMesure = $DateMesure->strftime("%H");
my $MinuteMesure = $DateMesure->strftime("%M");
my $MnRange = (int ($MinuteMesure/5))*5;

#
#################################################################
## Suppression des données Semaine-2 pour ne pas encombrer la BD
#################################################################
#

if (!defined($dbh = DBI->connect("DBI:mysql:database=$Database;host=$HostDatabase;port=3306",$user,$pwd))) {
        print(STDERR "Unable to connect to DB $Database ($HostDatabase)\n");
        print("U");
}

my $RequetteSql = "";
my @num1= '';
my @num2= '';
my $Table1 = "";
my $Table2= "";
my $Pbx = "";
my $Valeur2='';
my $Val1='';
my @num6 = ();
my $Val2="";


	if (($ARGV[0] eq "index") && ($ARGV[1] eq "PBX") && ($ARGV[2])) {
	## list des champs
		my $PbxAddress = "\'".$ARGV[2]."\'";

		$RequetteSql ="select * from $TableName where (pbxaddress = $PbxAddress) and (day = $DayMesure) and (month = $MonthMesure ) and (year = $YearMesure ) and (mnrange = $MnRange) and (hh = $HeureMesure) group by trunkgroup, date, id,day,month,year,hh,mn,mnrange,pbxaddress,trgname,in1,out1, mixte1,trunk,maxit,weeknum ;";
		#print "requette1 => $RequetteSql\n";

		my $sth = $dbh->prepare($RequetteSql);
		$sth->execute;
		while( @num1 =$sth->fetchrow() ) 
		{
			$Table1=$num1[0];
			$Table2=$num1[1];
			print "$num1[9]\n";
			$Val1="|".$num1[0].$Val1;

		} # loop from database (all return value)
		$sth->finish;

	}elsif (($ARGV[0] eq "get") && ($ARGV[1] eq "PBX") && ($ARGV[2]) && ($ARGV[3]) && (defined $ARGV[4])  ) {
	# Pbx info
	
		my $PbxAddress = "\'".$ARGV[2]."\'";
		$RequetteSql ="select * from $TableName where (pbxaddress = $PbxAddress) and (day = $DayMesure) and (month = $MonthMesure ) and (year = $YearMesure ) and (mnrange = $MnRange) and (hh = $HeureMesure) and (trunkgroup =  $ARGV[4]);";
		#print " requette2 : $RequetteSql \n";
		my $sth = $dbh->prepare($RequetteSql);
		$sth->execute;
		my $compte = 0;
		my $Table2 = '';
		my $TempCal1 = 0;
		my $TempCal2 = 0;
		my $TempCal3 = 0;
		my $TempCal4 = 0;
		my $Valeur3 = '';

		while( @num2 =$sth->fetchrow() ) 
		{

			$Valeur3 = join('|',@num2);
			for $compte (0..$#num2) {chomp($num2[$compte]);	}
			$num2[11] = $num2[11] + $TempCal1; 	# In
			$TempCal1 = $num2[11];
			
			$num2[12] = $num2[12] + $TempCal2; 	# Out
			$TempCal2 = $num2[12];

			$num2[13] = $num2[13] + $TempCal3; 	# Mixe
			$TempCal3 = $num2[13];

			$num2[15] = $num2[15] + $TempCal4; 	# Max
			$TempCal4 = $num2[15];

			chomp($num2[10]); 			# Name
			chop($num2[10]);
			$Valeur2 = $num2[9].":".$num2[11].":".$num2[12].":".$num2[13].":".$num2[15].":".$num2[10];

		} # loop from database (all return value)
		$sth->finish;

		my @num7 = (split /:/,$Valeur2);
		#print "$Val2\n";
		if ($ARGV[3] eq "Trunk") { print "$num2[9]\n"; };
		if ($ARGV[3] eq "Name") { print "$num7[5]\n"; };
		if ($ARGV[3] eq "OutIn") { print "$num7[2]\n"; };
		if ($ARGV[3] eq "OutOut") { print "$num7[1]\n"; };
		if ($ARGV[3] eq "OutMixte") { print "$num7[3]\n"; };
		if ($ARGV[3] eq "OutMax") { print "$num7[4]\n"; };


		##### Fin Get Debut Query #####

	}elsif (($ARGV[0] eq "query") && ($ARGV[1] eq "PBX") && ($ARGV[2]) && ($ARGV[3]) ) {


		my $PbxAddress = "\'".$ARGV[2]."\'";
		$RequetteSql ="select * from $TableName where (pbxaddress = $PbxAddress) and (day = $DayMesure) and (month = $MonthMesure ) and (year = $YearMesure ) and (mnrange = $MnRange) and (hh = $HeureMesure) group by trunkgroup,date, id,day,month,year,hh,mn,mnrange,pbxaddress,trgname,in1,out1, mixte1,trunk,maxit,weeknum ;";
		#print " requette 3: $RequetteSql \n";
		my $sth = $dbh->prepare($RequetteSql);
		$sth->execute;

		while( @num1 =$sth->fetchrow() ) 
		{

			my $RequetteSql1 ="select * from $TableName where (pbxaddress = $PbxAddress) and (day = $DayMesure) and (month = $MonthMesure ) and (year = $YearMesure ) and (mnrange = $MnRange) and (hh = $HeureMesure) and (trunkgroup =  $num1[9]);";

			my $sth1 = $dbh->prepare($RequetteSql1);
			$sth1->execute;

			my $compte = 0;
			my $Table2 = '';
			my $TempCal1 = 0;
			my $TempCal2 = 0;
			my $TempCal3 = 0;
			my $TempCal4 = 0;
			my $Val3="";

			while( @num2 =$sth1->fetchrow() ) 
			{
				$Val3 = join('|',@num2);
				for $compte (0..$#num2) {chomp($num2[$compte]);	}
				$num2[11] = $num2[11] + $TempCal1; 	# In
				$TempCal1 = $num2[11];

				$num2[12] = $num2[12] + $TempCal2; 	# Out
				$TempCal2 = $num2[12];

				$num2[13] = $num2[13] + $TempCal3; 	# Mixe
				$TempCal3 = $num2[13];

				$num2[15] = $num2[15] + $TempCal4; 	# Max
				$TempCal4 = $num2[15];

				chomp($num2[10]); 			# Name
				chop($num2[10]);
				$Val2 = $num2[9].":".$num2[11].":".$num2[12].":".$num2[13].":".$num2[15].":".$num2[10];

			} # loop from database (trunk)
			my @num7 = (split /:/,$Val2);
			#print "$Val2\n";

			if ($ARGV[3] eq "Trunk") { print "$num1[9]:$num1[9]\n"; };
			if ($ARGV[3] eq "Name") { print "$num1[9]:$num7[5]\n"; };
			if ($ARGV[3] eq "In") { print "$num1[9]:$num7[1]\n"; };
			if ($ARGV[3] eq "Out") { print "$num1[9]:$num7[2]\n"; };
			if ($ARGV[3] eq "Mixte") { print "$num1[9]:$num7[3]\n"; };
			if ($ARGV[3] eq "Max") { print "$num1[9]:$num7[4]\n"; };

			#print "val : @num7\n";

			#print "$Val2\n";

			$sth1->finish;
		} # loop from database (all return value)
		$sth->finish;

		#my @num4 = (split /|+/, $Table1);
		my @num4 = (split /\|/,$Table1);
		my @num5 = (split /\|/,$Valeur2);
		my $compte = 0 ;

		for $compte (1 .. $#num5) {


			if (($compte == 2) && ($ARGV[4] eq "Max")) { print "$num5[$compte+6]:$num5[$compte]"."\n";      };	# MaxIt
			if (($compte == 4) && ($ARGV[4] eq "Mixte")) { print "$num5[$compte+4]:$num5[$compte]"."\n";     };	# MIXEIT
			if (($compte == 5) && ($ARGV[4] eq "Out")) { print "$num5[$compte+3]:$num5[$compte]"."\n";       };	# OUT
			if (($compte == 6) && ($ARGV[4] eq "In")) { print "$num5[$compte+2]:$num5[$compte]"."\n";       };	# IN
			if (($compte == 7) && ($ARGV[4] eq "Name")) { print "$num5[$compte+1]:$num5[$compte]"."\n";       };	# TG NAME
			#if (($compte == 8) && ($ARGV[4] eq "Trunk")) { print "$num5[$compte]"."\n";       };			# TrKGRP
			#if ($compte == 9) { print "$num4[$compte]:$num5[$compte]"."\n";       };	# ADDRESS

		}


	}


# Disconnect

$dbh->disconnect;

exit;
