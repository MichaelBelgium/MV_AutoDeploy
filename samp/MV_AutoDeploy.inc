#include <a_samp>
#include <zcmd>
#include <a_mysql>
#include <sscanf2>

#define CHECK_UPDATE		30		//check every x minutes for update
#define MV_DIALOG			898		//choose a dialogid without response
#define UPDATES_LIMIT		10		//Last x commits will be shown in /updates
#define RESTART_ON_UPDATE	true 	//restart the server automaticly when an update get pushed

#define COL_PARAM			"{AFE7FF}"
#define COL_SERVER			"{3FCD02}"
#define COL_WHITE			"{FFFFFF}"

new MySQL:gConnection, gTimer = -1;

enum
{
	SERVER_UPDATE,
	SERVER_UPDATE_DEV,
	SERVER_ISSUE,
	SERVER_ISSUE_STATUSCHANGE,
	SERVER_TAG
};

forward CheckServerUpdate();
forward OnServerUpdateDetected(updateid, hash[], shorthash[], message[]);
forward OnUpcomingUpdateDetected(updateid, hash[], shorthash[], message[]);
forward OnServerIssueCreated(issueid, title[], priority[], kind[]);
forward OnServerIssueStatusChange(issueid, title[], oldstatus[], newstatus[]);
forward OnServerTagCreated(updateid, linkedtohash[], tagname[]);

MV_AutoDeployInit(MySQL:sqlCon)
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
	new string[256], hash[128], shash[16], msg[128], id, type, bool:needsrestart = false;

	new Cache:result = mysql_query(gConnection, "SELECT uID, Hash, Type, Message FROM Update_Data WHERE Handled = 0 ORDER BY Date DESC");
	new rows = cache_num_rows();

	if(rows > 0)
	{
		for(new i = 0; i < rows; i++)
		{
			cache_get_value_name_int(i, "uID", id);
			cache_get_value_name(i, "Hash", hash);
			cache_get_value_name(i, "Message", msg);
			cache_get_value_name_int(i, "Type", type);
			strmid(shash, hash, 0, 7);

			switch(type)
			{
				case SERVER_UPDATE:
				{
					CallLocalFunction("OnServerUpdateDetected", "isss", id, hash, shash, msg);
					needsrestart = RESTART_ON_UPDATE;
				}
				case SERVER_UPDATE_DEV: CallLocalFunction("OnUpcomingUpdateDetected", "isss", id, hash, shash, msg);
				case SERVER_ISSUE:
				{
					new issueid, priority[16], kind[64];
					sscanf(hash, "p</>is[16]s[64]", issueid, priority, kind);
					CallLocalFunction("OnServerIssueCreated", "isss", issueid, msg, priority, kind);
				}
				case SERVER_ISSUE_STATUSCHANGE:
				{
					new issueid, oldstatus[16], newstatus[16];
					sscanf(hash, "p</>is[16]s[16]", issueid, newstatus, oldstatus);
					CallLocalFunction("OnServerIssueStatusChange", "isss", issueid, msg, oldstatus, newstatus);
				}
				case SERVER_TAG: CallLocalFunction("OnServerTagCreated", "iss", id, hash, msg);
			}

			mysql_format(gConnection, string, sizeof(string), "UPDATE Update_Data SET Handled = 1 WHERE uID = %i", id);
			mysql_query(gConnection, string, false);
		}

		if(needsrestart)
		{
			SendClientMessageToAll(-1, "Server restarting ...");
			SendRconCommand("gmx");
		}
	}

	cache_delete(result);
}

CMD:updates(playerid,params[])
{
	new updates[256*5],string[256], Cache:result, totalrows, updaterows, last_type;
	new data[3][128], length = 0;

	result = mysql_query(gConnection, "SELECT uID FROM Update_Data WHERE Type = 0 OR Type = 1");
	totalrows = cache_num_rows();
	cache_delete(result);

	mysql_format(gConnection,string, sizeof(string), "SELECT * FROM Update_Data WHERE Type = 0 OR Type = 1 ORDER BY Date DESC LIMIT %i", UPDATES_LIMIT);
	result = mysql_query(gConnection, string);
	updaterows = cache_num_rows();

	format(string, sizeof(string), COL_WHITE"There are a total of %i updates.\n\n", totalrows);
	strcat(updates, string);

	length = strlen(string);

	if(updaterows > 0)
	{
		for(new i = 0; i < updaterows; i++)
		{
			cache_get_value_name(i, "Hash", data[0]);
			strmid(data[0], data[0], 0, 7);

			cache_get_value_name(i, "Message", data[1]);
			cache_get_value_name(i, "Date", data[2]);
			cache_get_value_name_int(i, "Type", last_type);

			if(last_type == SERVER_UPDATE)
				format(string, sizeof(string), COL_WHITE"[%s] "COL_SERVER"'%s' "COL_WHITE"at "COL_PARAM"%s\n", data[0], data[1], data[2]);
			else
				format(string, sizeof(string), "\t"COL_WHITE"[%s] "COL_SERVER"'%s' "COL_WHITE"at "COL_PARAM"%s\n", data[0], data[1], data[2]);

			strcat(updates, string);
		}

		if(last_type != SERVER_UPDATE)
			strins(updates, "{3FA3C4}Update in development:\n", length);

		ShowPlayerDialog(playerid, MV_DIALOG, DIALOG_STYLE_MSGBOX, "Update log", updates, "OK", "");
	}
	else
		SendClientMessage(playerid, -1, "No updates.");

	cache_delete(result);
	return 1;
}

CMD:issues(playerid,params[])
{
	new issues[256*5], string[128], Cache:result, rows = 0, Cache:old;
	new data[3][64], issueid, issuedata[2][16];

	result = mysql_query(gConnection, "SELECT Hash, Message, Date FROM Update_Data WHERE Type = 2 ORDER BY Date DESC LIMIT "#UPDATES_LIMIT);
	rows = cache_num_rows();
	old = cache_save();

	if(rows > 0)
	{
		for(new i = 0; i < rows; i++)
		{
			cache_get_value_name(i, "Hash", data[0]);
			cache_get_value_name(i, "Message", data[1]);
			cache_get_value_name(i, "Date", data[2]);

			sscanf(data[0], "p</>iss", issueid, issuedata[0], issuedata[1]);

			format(string, sizeof(string), COL_SERVER"Issue: "COL_WHITE"%i\t%s\t[%s - %s] "COL_SERVER"%s\n", issueid, data[2], issuedata[0], issuedata[1], data[1]);
			strcat(issues, string);

			mysql_format(gConnection, string, sizeof(string), "SELECT Date FROM Update_Data WHERE (Hash LIKE '%i/resolved%%' OR Hash LIKE '%i/closed%%') AND Type = 3", issueid, issueid);
			result = mysql_query(gConnection, string);

			if(cache_num_rows())
			{
				cache_get_value_name(0, "Date", data[2]);
				format(string, sizeof(string), COL_SERVER"\t» Fixed at "COL_WHITE"%s\n\n",data[2]);
				strcat(issues, string);
			}

			cache_delete(result);
			cache_set_active(old);
		}
	}
	else
		issues = "No issues.";

	cache_delete(old);

	ShowPlayerDialog(playerid, MV_DIALOG, DIALOG_STYLE_MSGBOX, "Latest issues", issues, "OK", "");
	return 1;
}
