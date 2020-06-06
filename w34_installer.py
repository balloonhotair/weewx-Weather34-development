from zipfile import ZipFile
from configobj import ConfigObj
import distutils.dir_util
import traceback
import sys
import time
import grp
import pwd
import os

def change_permissions_recursive(path_list, paths, paths_values):
    uid = pwd.getpwnam("www-data").pw_uid
    gid = grp.getgrnam("www-data").gr_gid
    for p in path_list:
        os.chmod(p, 0o755)
        os.chown(p, uid, gid)
        for root, dirs, files in os.walk(p, topdown=False):
            for directory in [os.path.join(root,d) for d in dirs]:
                os.chmod(directory, 0o755)
                os.chown(directory, uid, gid)
                if "json_day" in directory:
                    os.chmod(directory, 0o777)
            for filename in [os.path.join(root, f) for f in files]:
                os.chmod(filename, (0o777 if "wee_reports_w34" in filename else 0o755))
                os.chown(filename, uid, gid)
                if "plots_config.js" in filename:
                    with open(filename, 'r') as infile:
                        config_lines = infile.readlines()
                    count = 0
                    for j in range(len(config_lines)):
                        for i in range(len(paths)):
                            if paths[i] in config_lines[j]:
                                parts = config_lines[j].split("//")
                                config_lines[j] = paths[i] + "'" + paths_values[i] + "'" + "     //" + parts[1]
                                count +=1
                        if count >= len(paths):
                            break            
                    with open(filename, 'w') as outfile:
                        for i in config_lines:
                            outfile.write(i)

def find_replace(d, k, v, do_overwite, append = False):
    found = k in d
    if found:
        if do_overwrite:
            if append:
                if not v in d[k]:
                    d[k].append(v)
            else:
                d[k] = eval(v)
        else:
            print("Config entry " + k + " already exists and over_write is set to False")
    else:
        for value in d.values():
            if isinstance(value, dict):
                found = find_replace(value, k, v, do_overwrite, append)
            if found: 
                break
    return found

conf_files = {}
try:
    file_count = 1
    files = [f for f in os.listdir('.') if os.path.isfile(f) and f.endswith(".conf")]
    for f in files:
        conf_files[file_count] = f
        print(str(file_count) + " -> " + f)
        file_count += 1
    response = 0
    while response == 0 or response > len(conf_files):
        try:
            response = int(raw_input("Enter the NUMBER of the installer config file ").strip())
        except:
            response = int(input("Enter the NUMBER of the installer config file ").strip())
    conf_file = conf_files[response]
    print("Installer Config file " + conf_file + " was chosen.")
    with open(conf_file) as infile:
        d = eval(infile.read().replace("\n", "").replace("\t", ""))
        copy_list = list(d["copy_paths"].split(","))
        do_overwrite = True if d["over_write"] == "True" else False
        extract_path = d["extract_to_path"]
        if extract_path == None or len(extract_path) == 0:
            extract_path = "temp"
        zip_file = d["zip_file"]
        if len(zip_file) > 0:
            with ZipFile(zip_file, 'r') as zip_file:
                if not os.path.exists(extract_path):
                    os.makedirs(extract_path)
                else:
                    if not do_overwrite:
                        print ("Extract path exists and overwrite set to false aborting install")
                        sys.exit(1)
                print('Extracting all the files to ' + extract_path)
                zip_file.extractall(extract_path)
                print('Files extracted')
        try:
            for i in xrange(0, len(copy_list), 2):
                distutils.dir_util.copy_tree(os.path.join(extract_path, copy_list[i+1].strip()), copy_list[i].strip(), update = do_overwrite)
        except:
            for i in range(0, len(copy_list), 2):
                distutils.dir_util.copy_tree(os.path.join(extract_path, copy_list[i+1].strip()), copy_list[i].strip(), update = do_overwrite)
        locations = {copy_list[i+1]:copy_list[i] for i in range(0, len(copy_list), 2)}
        paths = ["var pathweewx = ", "var pathpws = ", "var pathweewxbin = "]     
        paths_values = ["/weewx" + locations["www"].split("weewx")[1].split("/")[0] + "/", '/weewx' + locations["www"].split("weewx")[1], locations["user"].split("/user/")[0]]
        change_permissions_recursive([locations["www"]], paths, paths_values)
        if d["delete_extracted_files"] == "True":
            if extract_path != os.getcwd():
                distutils.dir_util.remove_tree(extract_path)
        weewx_config_file = d["weewx_config_file"]
        distutils.file_util.copy_file(weewx_config_file, weewx_config_file + "." + str(int(time.time())))
        print('Updating weewx config')
        config_data = ConfigObj(weewx_config_file, encoding='utf8')
        for i in d:
            if i.startswith("config_entries"):
                if "append" in i:
                    parts = d[i].split("=", 1)
                    if not find_replace(config_data, parts[0].strip(), parts[1], do_overwrite, True):
                        print("Append to key " + parts[0] + " skipped because key was not found")
                else:
                    parts = d[i].split("=", 1)
                    zpath = parts[0]
                    parts = parts[1].split(":", 1)
                    key = parts[0].replace("'", "").strip()
                    if not find_replace(config_data, key, parts[1], do_overwrite):
                        if zpath == None or len(zpath) == 0:
                            config_data[key] = eval(parts[1])
                            config_data.comments[key] = ['#\n################################################################################\n']
                        else:
                            config_data[zpath][key] = eval(parts[1])
                            config_data[zpath].comments[key] = ['#\n    ############################################################################\n']
        config_data.write()
        print('Done!')
except Exception as e:
    traceback.print_exc()
    print (e)
