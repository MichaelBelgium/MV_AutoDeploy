#include <a_samp>
#include <zcmd>
#include <a_mysql>

#define CHECK_UPDATE		1		//check every x minutes for update
#define MV_DIALOG			898		//choose a dialogid without response
#define UPDATES_LIMIT		10		//Last x commits will be shown in /updates

#define COL_PARAM			"{AFE7FF}"
#define COL_SERVER			"{3FCD02}"
#define COL_WHITE			"{FFFFFF}"

new gConnection = -1, gTimer = -1;

forward CheckServerUpdate();
forward OnServerUpdateDetected(id, hash[], shorthash[], message[]);

MV_AutoDeployInit(sqlCon)
{
	gConnection = sqlCon;

	gTimer = SetTimer("CheckServerUpdate", CHECK_UPDATE*60000, true);
}

MV_AutoDeployExit()
{
	KillTimer(gTimer);
}

public CheckServerUpdate()
{
	new string[256], hash[128], shash[16], msg[128], id;

	new Cache:result = mysql_query(gConnection, "SELECT uID, Hash, Message FROM Update_Data WHERE Branch = 'master' AND Handled = 0 ORDER BY Date DESC");
	new rows = cache_num_rows(gConnection);
	if(rows > 0)
	{
		for(new i = 0; i < rows; i++)
		{
			id = cache_get_field_content_int(i, "uID", gConnection);
			cache_get_field_content(i, "Hash", hash, gConnection);
			cache_get_field_content(i, "Message", msg, gConnection);
			strmid(shash, hash, 0, 7);

			CallLocalFunction("OnServerUpdateDetected", "isss", id, hash, shash, msg);

			mysql_format(gConnection, string, sizeof(string), "UPDATE Update_Data SET Handled = 1 WHERE uID = %i", id);
			mysql_query(gConnection, string, false);
		}

		SendClientMessageToAll(-1, "Server restarting ...");
		SendRconCommand("gmx");
	}
	else
		print("No new updates.");

	cache_delete(result, gConnection);
}

CMD:updates(playerid,params[])
{
	new updates[256*5],string[128], Cache:result, rows[2], last_branch[32];
	new data[4][64];

	result = mysql_query(gConnection, "SELECT uID FROM Update_Data");
	rows[0] = cache_num_rows(gConnection);
	cache_delete(result, gConnection);

	mysql_format(gConnection,string, sizeof(string), "SELECT * FROM Update_Data ORDER BY Date DESC LIMIT %i", UPDATES_LIMIT);
	result = mysql_query(gConnection, string);
	rows[1] = cache_num_rows(gConnection);

	format(string, sizeof(string), COL_WHITE"There are a total of %i updates.\n\n", rows[0]);
	strcat(updates, string);

	rows[0] = strlen(string);

	if(rows[1] > 0)
	{
		for(new i = 0; i < rows[1]; i++)
		{
			cache_get_field_content(i, "Hash", data[0], gConnection);
			strmid(data[0], data[0], 0, 7);

			cache_get_field_content(i, "Message", data[1], gConnection);
			cache_get_field_content(i, "Date", data[2], gConnection);
			cache_get_field_content(i, "Branch", data[3], gConnection);

			if(!strcmp(data[3], "master", true))
				format(string, sizeof(string), COL_WHITE"[%s] "COL_SERVER"'%s' "COL_WHITE"at "COL_PARAM"%s\n", data[0], data[1], data[2]);
			else
				format(string, sizeof(string), "\t"COL_WHITE"[%s] "COL_SERVER"'%s' "COL_WHITE"at "COL_PARAM"%s\n", data[0], data[1], data[2]);

			strcat(updates, string);

			if(i == 0)
				format(last_branch, sizeof(last_branch), "%s", data[3]);
		}

		if(strcmp(last_branch, "master", true) != 0)
			strins(updates, "{3FA3C4}Update in development:\n", rows[0]);

		ShowPlayerDialog(playerid, MV_DIALOG, DIALOG_STYLE_MSGBOX, "Update log", updates, "OK", "");
	}
	else
		SendClientMessage(playerid, -1, "No updates.");

	cache_delete(result, gConnection);
	return 1;
}
