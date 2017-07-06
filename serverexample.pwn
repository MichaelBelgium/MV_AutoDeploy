#include <a_samp>
#include <core>
#include <float>
#include <MV_AutoDeploy>

#define SQL_PASSWORD    ""
#define SQL_USER        ""
#define SQL_DB          ""
#define SQL_SERVER      "127.0.0.1"

new gCon = -1;

main()
{
	print("\n----------------------------------");
	print("  Test Script\n");
	print("----------------------------------\n");
}

	public OnGameModeInit()
{
	SetGameModeText("Test Script");

	gCon = mysql_connect(SQL_SERVER, SQL_USER, SQL_DB, SQL_PASSWORD);
	if(mysql_errno(gCon) != 0)	
		printf("Could not connect to database %s!", SQL_DB);
	else
		MV_AutoDeployInit(gCon);
	return 1;
}

public OnGameModeExit()
{
	MV_AutoDeployExit();
	return 1;
}

public OnServerUpdateDetected(id, hash[], shorthash[], message[])
{
	new string[128];
	format(string, sizeof(string),"New update available: %s (%s - %i)", message, shorthash, id);
	SendClientMessageToAll(-1, string);
	print(string);
	return 1;
}