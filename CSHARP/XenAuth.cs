using Newtonsoft.Json.Linq;
using System;
using System.Collections.Generic;
using System.Management;
using System.Net.Http;
using System.Threading.Tasks;

namespace XenAuth
{
	public static class XenAuth
	{
		private static readonly HttpClient httpclient = new HttpClient();

		public static async Task<bool> Status(string api)
		{
			var apiUri = new Uri(api) + "?status";
			var response = await httpclient.GetAsync(apiUri);

			if (response.IsSuccessStatusCode)
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		public static async Task<int> Compare(string username, string password, string api)
		{
			var apiUri = new Uri(api) + "?compare";

			var form = new FormUrlEncodedContent(new[]
			{
				new KeyValuePair<string, string>("username", username),
				new KeyValuePair<string, string>("password", password),
				new KeyValuePair<string, string>("hwid", GetHwid()),
			});

			try
			{
				HttpResponseMessage response = await httpclient.PostAsync(apiUri, form);

				if (response.IsSuccessStatusCode)
				{
					string json = await response.Content.ReadAsStringAsync();
					JObject jsonobject = JObject.Parse(json);
					string status = jsonobject["status"].ToString();

					return status == "Ok" ? 200 : (status == "hwid_missmatch" ? 403 : 404);
				}
				else
				{
					return (int)response.StatusCode;
				}
			}
			catch (Exception)
			{
				return -1;
			}
		}




		public static string GetHwid()
		{
			string hdd = string.Empty;
			string motherboardn = string.Empty;
			string cpu = string.Empty;

			try
			{
				ManagementObjectSearcher hdds = new ManagementObjectSearcher("SELECT * FROM Win32_DiskDrive");
				foreach (ManagementObject diskDrive in hdds.Get())
				{
					if (diskDrive["MediaType"] != null && diskDrive["MediaType"].ToString().Contains("Fixed"))
					{
						hdd = diskDrive["SerialNumber"].ToString().Trim();
						break;
					}
				}
			}
			catch (Exception) {}

			try
			{
				ManagementObjectSearcher motherboards = new ManagementObjectSearcher("SELECT * FROM Win32_BaseBoard");
				foreach (ManagementObject motherboard in motherboards.Get())
				{
					motherboardn = motherboard["SerialNumber"].ToString().Trim();
					break;
				}
			}
			catch (Exception) {}
			

			try
			{
				ManagementObjectSearcher searcher = new ManagementObjectSearcher("SELECT ProcessorId FROM Win32_processor");
				ManagementObjectCollection mbsList = searcher.Get();

				foreach (ManagementObject mo in mbsList)
				{
					cpu = mo["ProcessorId"].ToString();
					break;
				}
			}
			catch (Exception) {}
			

			return hdd + motherboardn + cpu + "XenAuth";
		}
	}
}
