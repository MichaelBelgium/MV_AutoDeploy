#include <a_samp>
#include <zcmd>
#include <a_mysql>

#define CHECK_UPDATE		30						//check every x minutes for update
#define FILE_NAME			"lastupdatehash.txt" 	//the file where to put the last hash update in
#define DIALOG_NORESPONSE	898						//choose a dialogid without response
#define UPDATES_LIMIT		10						//Last x commits will be shown in /updates

#define SQL_PASSWORD    ""
#define SQL_USER        ""
#define SQL_DB          ""
#define SQL_SERVER      "127.0.0.1"

#define COL_PARAM		"{AFE7FF}"
#define COL_SERVER		"{3FCD02}"
#define COL_WHITE		"{FFFFFF}"

new g_SQL = -1, g_Timer;

forward CheckServerUpdate();

public OnFilterScriptInit()
{
	printf("[MV]AutoDeply loaded - Listening to git pulls/database every %i minutes", CHECK_UPDATE );

	g_SQL = mysql_connect(SQL_SERVER, SQL_USER, SQL_DB, SQL_PASSWORD);
	if(mysql_errno(g_SQL) != 0)	printf("Could not connect to database %s!", SQL_DB);

	g_Timer = SetTimer("CheckServerUpdate", CHECK_UPDATE*60000, true);

	return 1;
}

public OnFilterScriptExit()
{
	KillTimer(g_Timer);
	mysql_close(g_SQL);
	return 1;
}

public CheckServerUpdate()
{
	new string[256], hash[2][128], tmp[128];

	new File:handle = fopen(FILE_NAME);
	if(handle)
		fread(handle, hash[0]);

	fclose(handle);

	new Cache:result = mysql_query(g_SQL, "SELECT Hash, Message FROM Update_Data WHERE Branch = 'master' ORDER BY Date DESC LIMIT 1");
	if(cache_num_rows(g_SQL) == 1)
	{
		cache_get_field_content(0, "Hash", hash[1], g_SQL);
		cache_get_field_content(0, "Message", tmp, g_SQL);

		cache_delete(result, g_SQL);
		
		if(!strcmp(hash[0],hash[1],false))
			print("No new updates available");
		else
		{
			format(string, sizeof(string),"New update available: %s - Server restarting ...", tmp);
			SendClientMessageToAll(-1, string);
			print(string);

			fremove(FILE_NAME);

			handle = fopen(FILE_NAME);
			fwrite(handle, hash[1]);
			fclose(handle);

			SendRconCommand("gmx");
		}
	}
}

CMD:updates(playerid,params[])
{
	new updates[256*5],string[128], Cache:result, rows[2];
	new data[4][64];

	result = mysql_query(g_SQL, "SELECT uID FROM Update_Data");
	rows[0] = cache_num_rows(g_SQL);
	cache_delete(result, g_SQL);

	mysql_format(g_SQL,string, sizeof(string), "SELECT * FROM Update_Data ORDER BY Date DESC LIMIT %i", UPDATES_LIMIT);
	result = mysql_query(g_SQL, string);
	rows[1] = cache_num_rows(g_SQL);

	format(string, sizeof(string), COL_WHITE"There are a total of %i updates.\n\n", rows[0]);
	strcat(updates, string);

	if(rows[1] > 0)
	{
		for(new i = 0; i < rows[1]; i++)
		{
			cache_get_field_content(i, "Hash", data[0], g_SQL);
			strmid(data[0], data[0], 0, 7);

			cache_get_field_content(i, "Message", data[1], g_SQL);
			cache_get_field_content(i, "Date", data[2], g_SQL);
			cache_get_field_content(i, "Branch", data[3], g_SQL);

			if(!strcmp(data[3], "master", true))
				format(string, sizeof(string), COL_WHITE"[%s] "COL_SERVER"'%s' "COL_WHITE"at "COL_PARAM"%s\n", data[0], data[1], data[2]);
			else
				format(string, sizeof(string), "\t"COL_WHITE"[%s] "COL_SERVER"'%s' "COL_WHITE"at "COL_PARAM"%s\n", data[0], data[1], data[2]);

			strcat(updates, string);
		}

		ShowPlayerDialog(playerid, DIALOG_NORESPONSE, DIALOG_STYLE_MSGBOX, "Update log", updates, "OK", "");
	}
	else
		SendClientMessage(playerid, -1, "No updates.");

	cache_delete(result, g_SQL);
	return 1;
}

